<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 14/12/2016
 * Time: 15:27
 */

namespace Notive\Ewarehousing\Controller\Adminhtml\Orders;

use Magento\Backend\App\Action\Context;
use Notive\Ewarehousing\Helper\Webservice;
use \Magento\Sales\Model\Order;

/**
 * Class Send
 * @package Notive\Ewarehousing\Controller\Adminhtml\Orders
 */
class Send extends \Magento\Backend\App\Action
{
    /** @var Webservice */
    private $webservice;

    /** @var Order */
    private $order;

    /**
     * Send constructor.
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $objectManager = $context->getObjectManager();
        $this->webservice = $objectManager->get('Notive\Ewarehousing\Helper\Webservice');
        $this->order = $objectManager->get('\Magento\Sales\Model\Order');
        parent::__construct($context);
    }

    /**
     * Execute
     * @return bool
     */
    public function execute()
    {
        $params = $this->_request->getParams();
        if (!$this->_isAllowed() || !isset($params['order_id'])) {
            $this->_redirect('admin');
            return false;
        }
        $orderId = $params['order_id'];
        $mageOrder = $this->order->load($orderId);
        if (!$mageOrder->getId()) {
            $this->getMessageManager()->addErrorMessage('eWarehousing: The order with id '.$orderId.' was not found.');
            $this->_redirect('sales/order');
            return false;
        }

        if ($this->webservice->getOrderShouldSend($mageOrder)) {
            $returned = $this->webservice->sendOrder($mageOrder);
            if ($returned) {
                $this->getMessageManager()->addSuccessMessage('eWarehousing: The order was successfully send to eWarehousing.');
            } else {
                $this->getMessageManager()->addErrorMessage('eWarehousing: Failed to send the order to eWarehousing, see order comments for more information.');
            }
        } else {
            $this->getMessageManager()->addNoticeMessage('eWarehousing: This order does not have the correct state to be send to eWarehousing');
        }
        $this->_redirect('sales/order/view', ['order_id' => $mageOrder->getId(),]);
        return false;
    }
}