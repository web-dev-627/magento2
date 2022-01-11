<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 14/12/2016
 * Time: 14:50
 */

namespace Notive\Ewarehousing\Plugin;

use \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor;
use \Magento\Framework\View\Element\AbstractBlock;
use \Magento\Backend\Block\Widget\Button\ButtonList;
use \Magento\Sales\Model\Order;
use Notive\Ewarehousing\Helper\Webservice;
use Notive\Ewarehousing\Model\Config\OrderStatuses;
use \Magento\Backend\Helper\Data;

/**
 * Class PluginBefore
 * @package Notive\Ewarehousing\Plugin
 */
class PluginBefore
{
    /** @var Order */
    private $order;

    /** @var Data */
    private $data;

    /** @var Webservice */
    private $webservice;

    /**
     * PluginBefore constructor.
     * @param Order $order
     * @param Data $data
     * @param Webservice $webservice
     */
    public function __construct(Order $order, Data $data, Webservice $webservice)
    {
        $this->order = $order;
        $this->data = $data;
        $this->webservice = $webservice;
    }

    /**
     * @param Interceptor $subject
     * @param AbstractBlock $context
     * @param ButtonList $buttonList
     */
    public function beforePushButtons(Interceptor $subject, AbstractBlock $context, ButtonList $buttonList)
    {
        $request = $context->getRequest();
        if ($request->getFullActionName() == 'sales_order_view') {
            $params = $request->getParams();
            if (isset($params['order_id'])) {
                $mageOrder = $this->order->load($params['order_id']);
            }
            if (isset($mageOrder)) {
                if ($this->webservice->getOrderShouldSend($mageOrder)) {
                    $url = $this->data->getUrl('ewarehousing/orders/send', ['order_id' => $mageOrder->getId()]);
                    $buttonList->add(
                        'send_to_ewarehousing',
                        [
                            'label' => __('Send to eWarehousing'),
                            'onclick' => "confirmSetLocation('Are you sure you want to send this order to eWarehousing?', '".$url."')",
                            'class' => 'go',
                        ],
                        -1
                    );
                }

                // TODO: New feature?
//                $orderState = $mageOrder->getStatus();
//                if ($orderState == OrderStatuses::ORDER_STATUS_CODE_SENT) {
//                    $url = $this->data->getUrl('ewarehousing/orders/get', ['order_id' => $mageOrder->getId()]);
//                    $buttonList->add(
//                        'update_from_ewarehousing',
//                        [
//                            'label' => __('Get from eWarehousing'),
//                            'onclick' => "confirmSetLocation('Are you sure?', '".$url."')",
//                            'class' => 'go',
//                        ],
//                        -1
//                    );
//                }
            }
        }
    }
}