<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 28/11/2016
 * Time: 16:17
 */

namespace Notive\Ewarehousing\Observer\Sales\Order;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Notive\Ewarehousing\Helper\Webservice;

/**
 * Class SaveAfter
 * @package Notive\Ewarehousing\Observer\Sales\Order
 */
class SaveAfter implements ObserverInterface
{
    /** @var Webservice */
    private $webservice;

    /**
     * SaveAfter constructor.
     * @param Webservice $webservice
     */
    public function __construct(Webservice $webservice)
    {
        $this->webservice = $webservice;
    }

    /**
     * Execute
     * @param Observer $observer
     * @return bool
     */
    public function execute(Observer $observer)
    {
        /** @var Order $mageOrder */
        $mageOrder = $observer->getEvent()->getData('order');
        if ($this->webservice->getOrderShouldSend($mageOrder)) {
            $returned = $this->webservice->sendOrder($mageOrder);
            if ($returned) {
                return true;
            }
        }
        return false;
    }
}