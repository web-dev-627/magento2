<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectMSI\Model;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Api\StockReducerInterface;

class StockReducer implements StockReducerInterface
{

    /**
     * @var ItemToSellInterfaceFactory
     */
    private $itemToSellFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PlaceReservationsForSalesEventInterface
     */
    private $placeReservationsForSalesEvent;

    /**
     * @var Processor
     */
    private $priceIndexer;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelFactory;

    /**
     * @var SalesEventInterfaceFactory
     */
    private $salesEventFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * SecondChance constructor.
     *
     * @param ItemToSellInterfaceFactory $itemToSellFactory
     * @param PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent
     * @param Processor $priceIndexer
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     * @param SalesEventInterfaceFactory $salesEventFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        ItemToSellInterfaceFactory $itemToSellFactory,
        PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent,
        Processor $priceIndexer,
        SalesChannelInterfaceFactory $salesChannelFactory,
        SalesEventInterfaceFactory $salesEventFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->itemToSellFactory = $itemToSellFactory;
        $this->placeReservationsForSalesEvent = $placeReservationsForSalesEvent;
        $this->priceIndexer = $priceIndexer;
        $this->salesChannelFactory = $salesChannelFactory;
        $this->salesEventFactory = $salesEventFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @param OrderInterface $order
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     */
    public function reduce(OrderInterface $order): void
    {
        if ($this->scopeConfig->getValue('cataloginventory/options/can_subtract')) {
            $products = $order->getAllItems();
            foreach ($products as $itemId => $product) {
                $this->placeReservation($product);
            }
        }
    }

    /**
     * @param $product
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     */
    private function placeReservation(OrderItemInterface $product): void
    {
        $itemsToUndoCancel[] = $this->getItemToSell($product);

        $store = $this->storeManager->getStore($product->getStoreId());

        $salesChannel = $this->getSalesChannel($store->getWebsiteId());
        $salesEvent = $this->getSalesEvent($product->getOrderId());

        $this->placeReservationsForSalesEvent->execute($itemsToUndoCancel, $salesChannel, $salesEvent);

        $this->priceIndexer->reindexRow($product->getProductId());
    }

    /**
     * @param OrderItemInterface $product
     * @return ItemToSellInterface
     */
    public function getItemToSell(OrderItemInterface $product): ItemToSellInterface
    {
        return $this->itemToSellFactory->create([
            'sku' => $product->getSku(),
            'qty' => -$product->getQtyCanceled()
        ]);
    }

    /**
     * @param $websiteId
     * @return SalesChannelInterface
     * @throws NoSuchEntityException
     */
    private function getSalesChannel($websiteId): SalesChannelInterface
    {
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();

        return $this->salesChannelFactory->create([
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode
            ]
        ]);
    }

    /**
     * @param $objectId
     * @return SalesEventInterface
     */
    private function getSalesEvent($objectId): SalesEventInterface
    {
        return $this->salesEventFactory->create([
            'type' => 'order_reordered',
            'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string)$objectId,
        ]);
    }
}
