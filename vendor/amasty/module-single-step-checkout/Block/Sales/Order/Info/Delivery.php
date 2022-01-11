<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */


namespace Amasty\Checkout\Block\Sales\Order\Info;

use Amasty\Checkout\Model\Delivery as DeliveryModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Delivery
 */
class Delivery extends Template
{
    /**
     * @var DeliveryModel
     */
    protected $delivery;

    /**
     * @var Session
     */
    protected $checkoutSession;

    public function __construct(
        Context $context,
        DeliveryModel $delivery,
        Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->delivery = $delivery;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/info/delivery.phtml');
    }

    /**
     * @return bool|int
     */
    private function getCurrentOrderId()
    {
        if ($orderId = $this->getOrderId()) {
            return $orderId;
        }

        if ($orderId = $this->getRequest()->getParam('order_id')) {
            return $orderId;
        }

        if ($lastRealOrder = $this->checkoutSession->getLastRealOrder()) {
            if ($orderId = $lastRealOrder->getId()) {
                return $orderId;
            }
        }

        return false;
    }

    /**
     * @return bool|int
     */
    private function getCurrentQuoteId()
    {
        if ($quoteId = $this->getQuoteId()) {
            return $quoteId;
        }

        if ($quoteId = $this->checkoutSession->getQuoteId()) {
            return $quoteId;
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function getDeliveryDateFields()
    {
        if ($orderId = $this->getCurrentOrderId()) {
            $delivery = $this->delivery->findByOrderId($orderId);
        } elseif ($quoteId = $this->getCurrentQuoteId()) {
            $delivery = $this->delivery->findByQuoteId($quoteId);
        } else {
            return false;
        }

        if (!$delivery->getId()) {
            return false;
        }

        return $this->getDeliveryFields($delivery);
    }

    /**
     * @param DeliveryModel $delivery
     *
     * @return array
     */
    public function getDeliveryFields($delivery)
    {
        $date = $delivery->getDate();
        $time = $delivery->getTime();

        $fields = [];
        if (!empty($date)) {
            $fields[] = [
                'label' => __('Delivery Date'),
                'value' => date('jS F, Y', strtotime($date))
            ];
        }

        if ($time !== null && $time >= 0) {
            $fields[] = [
                'label' => __('Delivery Time'),
                'value' => $time . ':00 - ' . (($time) + 1) . ':00',
            ];
        }

        if ($delivery->getComment()) {
            $fields[] = [
                'label' => __('Delivery Comment'),
                'value' => $delivery->getComment(),
            ];
        }

        return $fields;
    }
}
