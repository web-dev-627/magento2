<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 29/11/2016
 * Time: 12:07
 */

namespace Notive\Ewarehousing\Helper;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Notive\Ewarehousing\Model\Config\OrderStatuses;
use Notive\Ewarehousing\Model\EwarehousingOrder;

/**
 * Class Webservice
 * @package Notive\Ewarehousing\Helper
 */
class Webservice
{
    const WEBSERVICE_URL = 'http://ws.ewarehousing.nl';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var string */
    private $version;

    /** @var string */
    private $context;

    /** @var string */
    private $customerId;

    /**
     * Webservice constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Config $config, StoreManagerInterface $storeManager)
    {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        $this->customerId = $this->getConfig('authentication', 'customer_id');
        $this->context = $this->getContext();
        $this->version = $this->getVersion();
    }

    /**
     * Get config by group, field and scope
     * @param string $group
     * @param string $field
     * @return mixed
     */
    public function getConfig($group, $field, $storeId = 0)
    {
        return $this->scopeConfig->getValue('ewarehousing_section/'.$group.'/'.$field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function setConfig($group, $field, $value, $storeId = 0)
    {
        return $this->config->saveConfig('ewarehousing_section/'.$group.'/'.$field, $value, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * General call function
     * @param string $endpoint
     * @param array $data
     * @return array|mixed
     */
    private function call($endpoint, $data)
    {
        $data['plugin'] = [
            'plugin_version' => $this->version,
            'plugin_type' => 'MAGENTO2',
        ];

        $curlCall = curl_init();
        curl_setopt($curlCall, CURLOPT_URL, self::WEBSERVICE_URL.$endpoint.'?plugin_version='.$this->version.'&plugin_type=MAGENTO2');
        curl_setopt($curlCall, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlCall, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlCall, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlCall, CURLOPT_POST, true);
        curl_setopt($curlCall, CURLOPT_TIMEOUT, 300);
        curl_setopt($curlCall, CURLOPT_POSTFIELDS, http_build_query($data));

        if ($endpoint !== '/2/auth') {
            if (!$this->context) {
                return [
                    'success' => false,
                    'errors' => 'Could not authenticate',
                ];
            }
            curl_setopt($curlCall, CURLOPT_USERPWD, $this->customerId.':'.$this->context);
        }

        $response = curl_exec($curlCall);

        if ($response === false) {
            return [
                'success' => false,
                'errors' => 'Curl error: '.curl_error($curlCall),
            ];
        }

        return json_decode($response, true);
    }

    /**
     * Get authentication context
     * @return bool|mixed
     */
    private function getContext()
    {
        $response = $this->call('/2/auth', [
            'username' => $this->getConfig('authentication', 'username'),
            'password' => md5($this->getConfig('authentication', 'password')),
            'customer_id' => $this->customerId,
        ]);

        if (isset($response['context']) && !empty($response['context'])) {
            return $response['context'];
        }
        return false;
    }

    /**
     * Send the order to eWarehousing
     * @param Order $mageOrder
     * @return bool
     */
    public function sendOrder(Order $mageOrder)
    {
        // Don't send if already sent successfully to eWarehousing
        if ($mageOrder->getStatus() == OrderStatuses::ORDER_STATUS_CODE_SENT) {
            return true;
        }

        // Send order
        $ewhOrder = new EwarehousingOrder();
        $orderData = $ewhOrder->OrderToArray($mageOrder);
        $result = $this->call('/2/orders/create', $orderData);
        // Handle success response
        if (is_array($result) && isset($result['success']) && !isset($result['errors']) && $result['success']) {
            $mageOrder->addStatusHistoryComment('Order has been sent to eWarehousing', OrderStatuses::ORDER_STATUS_CODE_SENT);
            $mageOrder->save();
            return true;
        } elseif (isset($result['errors']) && !empty($result['errors'])) { // Handle failure
            if (is_array($result['errors'])) {
                $result['errors'] = implode(', ', $result['errors']);
            }
            $mageOrder->addStatusHistoryComment('Error while trying to send the order to eWarehousing: '.$result['errors'], OrderStatuses::ORDER_STATUS_CODE_ERROR);
        } else {
            $mageOrder->addStatusHistoryComment('Unknown error while trying to send the order to eWarehousing.', OrderStatuses::ORDER_STATUS_CODE_ERROR);
        }
        $mageOrder->save();
        return false;
    }

    /**
     * Check if the order should send to eWarehousing
     * @param Order $mageOrder
     * @return bool
     */
    public function getOrderShouldSend(Order $mageOrder)
    {
        $enabled = $this->getConfig('orders_send', 'enabled', $mageOrder->getStoreId());
        if($enabled) {
            $sendStates = explode(',', $this->getConfig('orders_send', 'status_send', $mageOrder->getStoreId()));
            $orderState = $mageOrder->getStatus();
            if (in_array($orderState, $sendStates) || $orderState == OrderStatuses::ORDER_STATUS_CODE_ERROR) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get track and trace information from eWarehousing
     * @param OrderCollection $mageOrders
     * @return array|mixed
     */
    public function getOrderCollectionTracking(OrderCollection $mageOrders)
    {
        $orderIds = [];
        /** @var Order $mageOrder */
        foreach ($mageOrders as $mageOrder) {
            $orderIds[] = $mageOrder->getRealOrderId();
        }

        $result = $this->call('/4/orders/tracking', [
            'reference' => $orderIds,
        ]);

        if (isset($result['success']) && !$result['success']) {
            return [];
        }

        if (isset($result['order_reference'])) {
            $result = [$result['order_reference'] => $result];
        }

//        $result  = $this->getDebugTrackingData($result); // Debug line

        return $result;
    }

    /**
     * Get stock levels from eWarehousing
     * @return array
     */
    public function getStock()
    {
        $lastSync = $this->getConfig('stock_sync', 'last_sync');
        if (empty($lastSync)) {
            $lastSync = '1970-01-01 00:00:00';
        }

        $page = 1;
        $return = array();
        $limit = 1000;
        while (true) {
            $data = [
                'updated_after' => $lastSync,
                'limit' => $limit,
                'page' => $page,
            ];
            $result = $this->call('/2/stock', $data);
            if (isset($result['success']) && !$result['success']) {
                break;
            }
            $return = array_merge($return, $result);
            $page++;
            if (count($result) < $limit) {
                break;
            }
        }
        $this->setConfig('stock_sync', 'last_sync', date('Y-m-d H:i:s'));
        return $return;
    }


    /**
     * Creates tracking data for all returned orders
     * @param array $result
     * @return array
     */
    private function getDebugTrackingData(array $result)
    {
        $debugArray = [];
        foreach ($result as $key => $item) {
            $debugArray[$key] = [
                'order_reference' => $item['order_reference'],
                'sent' => true,
                'zipcode' => '',
                'labels' => [
                    0 => [
                        'tracking_code' => '05218954180184',
                        'tracking_url' => 'https://tracking.dpd.de/cgi-bin/delistrack?pknr=05218954180184&typ=1&lang=nl',
                        'shipper' => 'DPD',
                    ],
                ],
            ];
        }

        return $debugArray;
    }

    /**
     * Gets version from module.xml
     * @return string
     */
    public function getVersion()
    {
        try {
            $curPath = realpath(dirname(__FILE__));
            $path = str_replace('Helper', 'etc'.DIRECTORY_SEPARATOR.'module.xml', $curPath);
            $xml = simplexml_load_file($path);
            $version = (string) $xml->module->attributes()['setup_version'];
        } catch (\Exception $e) {
            $version = 'v2_getVersion_error';
        }

        return $version;
    }
}