<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 09/12/2016
 * Time: 16:42
 */

namespace Notive\Ewarehousing\Cron;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Convert\Order as OrderConverter;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Shipping\Helper\Carrier;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;

use Notive\Ewarehousing\Helper\Webservice;
use Notive\Ewarehousing\Model\Config\OrderStatuses;
use Psr\Log\LoggerInterface;

/**
 * Class Orders
 * @package Notive\Ewarehousing\Cron
 */
class Orders
{
    /** @var  Webservice */
    private $webservice;
    /** @var LoggerInterface */
    private $logger;
    /** @var Order */
    private $order;
    /** @var OrderConverter */
    private $orderConverter;
    /** @var OrderCollection */
    private $orderCollection;
    /** @var ShipmentNotifier */
    private $shipmentNotifier;
    /** @var Track */
    private $track;
    /** @var Carrier */
    private $carrier;
    /** @var StoreManagerInterface */
    private $storeManager;
    /** @var TrackFactory */
    private $trackFactory;

    /**
     * Orders constructor.
     *
     * @param Webservice            $webservice
     * @param LoggerInterface       $logger
     * @param Order                 $order
     * @param OrderConverter        $orderConverter
     * @param OrderCollection       $orderCollection
     * @param ShipmentNotifier      $shipmentNotifier
     * @param Track                 $track
     * @param TrackFactory          $trackFactory
     * @param Carrier               $carrier
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Webservice $webservice,
        LoggerInterface $logger,
        Order $order,
        OrderConverter $orderConverter,
        OrderCollection $orderCollection,
        ShipmentNotifier $shipmentNotifier,
        Track $track,
        TrackFactory $trackFactory,
        Carrier $carrier,
        StoreManagerInterface $storeManager
    ) {
        $this->webservice = $webservice;
        $this->logger = $logger;
        $this->order = $order;
        $this->orderConverter = $orderConverter;
        $this->orderCollection = $orderCollection;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->track = $track;
        $this->trackFactory = $trackFactory;
        $this->carrier = $carrier;
        $this->storeManager = $storeManager;
    }

    /**
     * Update orders
     * @return $this
     */
    public function update()
    {
        $this->logger->info(__METHOD__);
        if (!$this->webservice->getConfig('orders_sync', 'enabled')) {
            return $this;
        }

        foreach($this->storeManager->getStores() as $mageStore) {
            if ($this->webservice->getConfig('orders_sync', 'enabled', $mageStore->getId())) {
                $this->updateOrders($mageStore->getId());
            }
        }

        return $this;
    }

    /**
     * Retry failed orders
     * @return $this
     */
    public function retry()
    {
        $this->logger->info(__METHOD__);

        if (!$this->webservice->getConfig('orders_send', 'enabled')) {
            return $this;
        }

        /** @var OrderCollection $mageOrders */
        $mageOrders = $this->order->getCollection()
            ->addFieldToFilter('status', OrderStatuses::ORDER_STATUS_CODE_ERROR);

        /** @var MageOrder $mageOrder */
        foreach ($mageOrders as $mageOrder) {
            if ($this->webservice->getOrderShouldSend($mageOrder)) {
                $history = $mageOrder->getStatusHistoryCollection()
                    ->addFieldToFilter('status', OrderStatuses::ORDER_STATUS_CODE_ERROR);
                $tries = count($history);
                $lastTry = $history->getFirstItem()->getCreatedAt();

                if ($this->getShouldSendRetry($tries, $lastTry)) {
                    $this->webservice->sendOrder($mageOrder);
                }
            }
        }
        $this->logger->info(__METHOD__.': Successful');

        return $this;
    }

    /**
     * @param string $tries
     * @param int $lastTry
     * @return bool
     */
    private function getShouldSendRetry($tries, $lastTry)
    {
        $lastTry = new \DateTime($lastTry);

        if ($tries <= 4) {
            return true;
        } elseif ($tries >= 5 && $tries <= 10 && $lastTry <= new \DateTime('2 hours ago')) {
            return true;
        } elseif ($tries >= 11 && $tries <= 15 && $lastTry <= new \DateTime('12 hours ago')) {
            return true;
        }
        return false;
    }

    /**
     * Update orders
     *
     * @param $storeId
     */
    private function updateOrders($storeId)
    {
        /** @var OrderCollection $mageOrders */
        $mageOrders = $this->order->getCollection()
            ->addFieldToFilter('status', OrderStatuses::ORDER_STATUS_CODE_SENT)
            ->addFieldToFilter('store_id', $storeId)
        ;
        $mageOrderArray = [];
        /** @var MageOrder $mageOrder */
        foreach ($mageOrders as $mageOrder) {
            $mageOrderArray[$mageOrder->getRealOrderId()] = $mageOrder;
        }
        $trackingArray = $this->webservice->getOrderCollectionTracking($mageOrders);

        foreach ($trackingArray as $tracking) {
            $reference = $tracking['order_reference'];
            if (!isset($mageOrderArray[$reference])) {
                continue;
            }
            $mageOrder = $mageOrderArray[$reference];

            if (!$tracking['sent'] || !$mageOrder->canShip()) {
                continue;
            }

            if(!$tracking['labels']) {
                $this->logger->info('No labels found');
                continue;
            }

            $this->shipOrder($mageOrder, $tracking);
        }
    }

    /**
     * @param $order
     * @param $tracking
     */
    private function shipOrder($order, $tracking) {
        /** @var MageOrder\Item[] $orderItems */
        /** @var Shipment $shipment */
        $orderId = $order->getRealOrderId();
        $this->logger->info('Shipping '.$orderId);

        try{
            $shipment = $this->orderConverter->toShipment($order);
            $orderItems = $order->getAllItems();
            foreach ($orderItems as $orderItem) {
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }
                $qtyShipped = $orderItem->getQtyToShip();
                /** @var MageOrder\Shipment\Item $shipmentItem */
                $shipmentItem = $this->orderConverter->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                $shipment->addItem($shipmentItem);
            }

            $shipment->register();
            foreach ($tracking['labels'] as $label) {

                $data = array(
                    'carrier_code' => $label['shipper'],
                    'title' => $label['shipper'],
                    'number' => $label['tracking_url'],
                );

                if (empty($data['number']) && !empty($label['tracking_code'])) {
                    $data['number'] = $label['tracking_code'];
                }

                $track = $this->trackFactory->create()->addData($data);
                $shipment->addTrack($track)->save();
            }

            $shipment->getOrder()->setIsInProcess(true);
            $shipment->save();
            $shipment->getOrder()->addStatusHistoryComment('Shipment has been created by eWarehousing.', Order::STATE_COMPLETE);
            $shipment->getOrder()->save();
            $this->shipmentNotifier->notify($shipment);
        } catch (\Exception $ex) {
            $this->logger->info('Shipping failed  '.$orderId);
        }
    }
}
