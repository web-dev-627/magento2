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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before your update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MultiSafepay\Connect\Model;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AppInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\StatusResolver;
use Magento\Sales\Model\OrderNotifier;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Connect\Helper\AddressHelper;
use MultiSafepay\Connect\Helper\Data as HelperData;
use MultiSafepay\Connect\Helper\RefundHelper;
use MultiSafepay\Connect\Helper\UndoCancel;
use MultiSafepay\Connect\Model\Api\MspClient;
use MultiSafepay\Connect\Model\Config\Source\Creditcards;
use MultiSafepay\Connect\Model\MultisafepayTokenizationFactory;
use MultiSafepay\Connect\Model\Url;

class Connect extends \Magento\Payment\Model\Method\AbstractMethod
{
    const KEY_SUB_TOTAL = 'subtotal';
    const KEY_SHIPPING = 'shipping';
    const KEY_TAX = 'tax';
    const KEY_GRAND_TOTAL = 'grand_total';

    protected $_isInitializeNeeded = true;
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';
    public $issuer_id = null;
    protected $stockRegistry;

    /**
     * @var string
     */
    protected $_code = 'connect';

    /**
     * @var string
     */
    //protected $_infoBlockType = 'Magento\Paypal\Block\Payment\Info';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canOrder = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canAuthorize = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canSaveCc = false;

    /**
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canReviewPayment = false;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    /** @var $url Url */
    protected $url;
    protected $_requestHttp;
    protected $_currencyFactory;
    protected $_client;
    protected $_mspHelper;
    protected $_mspToken;
    public $_gatewayCode;
    protected $_product;
    protected $_productMetadataInterface;
    protected $_transactionRepositoryInterface;
    protected $_localeResolver;
    protected $_orderRepositoryInterface;
    protected $_orderNotifier;
    protected $_statusResolver;
    protected $_creditcards;
    public $_invoiceSender;
    public $banktransurl;
    protected $logger;
    public $_manualGateway = null;
    public $_isAdmin = false;
    protected $refundHelper;
    protected $restrictions;

    protected $dataObjectFactory;
    /**
     * @var AddressHelper
     */
    protected $addressHelper;

    /**
     * @var UndoCancel
     */
    protected $undoCancel;

    /**
     * Connect constructor.
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \Magento\Framework\Module\ModuleListInterface                $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $localeDate
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Checkout\Model\Session                              $checkoutSession
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param \Magento\Framework\App\RequestInterface                      $requestHttp
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface         $stockRegistry
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender        $invoiceSender
     * @param \Magento\Framework\App\ProductMetadataInterface              $productMetadataInterface
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface                $invoiceRepositoryInterface
     * @param \Magento\Sales\Api\TransactionRepositoryInterface            $transactionRepositoryInterface
     * @param \Magento\Framework\Locale\Resolver                           $localeResolver
     * @param \Magento\Sales\Api\OrderRepositoryInterface                  $orderRepositoryInterface
     * @param \Magento\Sales\Model\OrderNotifier                           $orderNotifier
     * @param \Magento\Sales\Model\Order\StatusResolver                    $statusResolver
     * @param \Magento\Directory\Model\CurrencyFactory                     $currencyFactory
     * @param \MultiSafepay\Connect\Model\MultisafepayTokenizationFactory  $multisafepayTokenizationFactory
     * @param \MultiSafepay\Connect\Model\Api\MspClient                    $mspClient
     * @param \MultiSafepay\Connect\Helper\Data                            $helperData
     * @param \MultiSafepay\Connect\Model\Url                              $url
     * @param \MultiSafepay\Connect\Model\Config\Source\Creditcards        $creditcards
     * @param \Magento\Customer\Model\Session                              $customerSession
     * @param \MultiSafepay\Connect\Helper\AddressHelper                   $addressHelper
     * @param \MultiSafepay\Connect\Helper\RefundHelper                    $refundHelper
     * @param \MultiSafepay\Connect\Helper\UndoCancel                      $undoCancel
     * @param \MultiSafepay\Connect\Model\GatewayRestrictions              $restrictions
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ModuleListInterface $moduleList,
        TimezoneInterface $localeDate,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        UrlInterface $urlBuilder,
        RequestInterface $requestHttp,
        StockRegistryInterface $stockRegistry,
        InvoiceSender $invoiceSender,
        ProductMetadataInterface $productMetadataInterface,
        InvoiceRepositoryInterface $invoiceRepositoryInterface,
        TransactionRepositoryInterface $transactionRepositoryInterface,
        Resolver $localeResolver,
        OrderRepositoryInterface $orderRepositoryInterface,
        OrderNotifier $orderNotifier,
        StatusResolver $statusResolver,
        CurrencyFactory $currencyFactory,
        MultisafepayTokenizationFactory $multisafepayTokenizationFactory,
        MspClient $mspClient,
        HelperData $helperData,
        Url $url,
        Creditcards $creditcards,
        \Magento\Customer\Model\Session $customerSession,
        RefundHelper $refundHelper,
        AddressHelper $addressHelper,
        UndoCancel $undoCancel,
        GatewayRestrictions $restrictions,
        DataObjectFactory $dataObjectFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->dataObjectFactory = $dataObjectFactory;
        $this->_customerSession = $customerSession;
        $this->_client = $mspClient;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
        $this->_requestHttp = $requestHttp;
        $this->_currencyFactory = $currencyFactory;
        $this->addressHelper = $addressHelper;
        $this->refundHelper = $refundHelper;
        $this->undoCancel = $undoCancel;
        $this->restrictions = $restrictions;

        $this->_mspHelper = $helperData;
        $this->_mspToken = $multisafepayTokenizationFactory;
        $this->_creditcards = $creditcards;
        $this->url = $url;

        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');

        $this->_invoiceSender = $invoiceSender;
        $this->stockRegistry = $stockRegistry;
        $this->_transactionRepositoryInterface = $transactionRepositoryInterface;

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/multisafepay.log');
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($writer);
        $this->_client->logger = $this->logger;
        $this->_client->debug = ($this->getMainConfigData('msp_debug')) ? true : false;

        $app_state = $this->_appState;
        $area_code = $app_state->getAreaCode();

        $this->_productMetadataInterface = $productMetadataInterface;
        $this->_localeResolver = $localeResolver;
        $this->_orderRepositoryInterface = $orderRepositoryInterface;
        $this->_orderNotifier = $orderNotifier;
        $this->_statusResolver = $statusResolver;

        $invoiceId = $requestHttp->getParam('invoice_id');
        if ($invoiceId && $app_state->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            $invoice = $invoiceRepositoryInterface->get($invoiceId);
            if ($invoice) {
                //the invoice is loaded so we can check the invoice currencies.
                $base_currency_code = $invoice->getBaseCurrencyCode();
                $order_currency_code = $invoice->getOrderCurrencyCode();
                if ($base_currency_code != $order_currency_code) {
                    $this->_canRefund = false;
                    $this->_canRefundInvoicePartial = false;
                }

                /*
                 * Refunding from the Magento backend is disabled when the order processed a Fooman Surcharge
                 * This is done because the Fooman extension has an issue with partial refunds, causing wrong amounts refunded online at MultiSafepay
                 * Issue has been reported at Fooman, once resolved this functionality will be supported again.
                 */
                $extensionAttributes = $invoice->getExtensionAttributes();
                if ($extensionAttributes) {
                    if (method_exists($extensionAttributes, 'getFoomanTotalGroup')) {
                        $invoiceTotalGroup = $extensionAttributes->getFoomanTotalGroup();
                        if ($invoiceTotalGroup) {
                            $items = $invoiceTotalGroup->getItems();
                            if (!empty($items)) {
                                $this->_canRefund = false;
                                $this->_canRefundInvoicePartial = false;
                            }
                        }
                    }
                }
            }
        }
    }

    public function transactionRequest($order, $resetGateway = false)
    {
        $params = $this->_requestHttp->getParams();

        if (isset($params['issuer']) && $params['issuer'] != "null") {
            $this->issuer_id = $params['issuer'];
        }
        $billing = $order->getBillingAddress();
        if ($order->canShip()) {
            $shipping = $order->getShippingAddress();
        }

        if (is_null($order->getPayment())) {
            return false;
        }
        $this->_gatewayCode = $order->getPayment()->getMethodInstance()->_gatewayCode;

        $recurring = null;

        if (isset($params['recurring_hash']) &&
            $params['recurring_hash'] != "" &&
            $this->_mspHelper->isEnabled('tokenization') &&
            !in_array($params['recurring_hash'], $this->_creditcards->tokenizationSupported())
        ) {

            $recurringId = $this->_mspHelper->getRecurringIdByHash(
                $params['recurring_hash']
            );
            $recurring = $this->_mspToken->create()->load($recurringId);
            $this->_gatewayCode = $recurring['cc_type'];
            if ($recurring['customer_id'] !== $this->_customerSession->getCustomer()->getId()) {
                $recurring = null;
            }
        }

        $environment = $this->getMainConfigData('msp_env');

        $magentoInfo = $this->_productMetadataInterface;

        $this->initializeClient($environment, $order);

        $items = "<ul>\n";
        foreach ($order->getAllVisibleItems() as $item) {
            $items .= "<li>" . ($item->getQtyOrdered() * 1) . " x : " . $item->getName() . "</li>\n";
        }
        $items .= "</ul>\n";

        $dataCheck = $this->getConfigData('days_active', null, $order->getPayment()->getMethodInstance()->getCode());
        if (isset($dataCheck)) {
            $daysActive = $dataCheck;
        } else {
            $daysActive = '30';
        }

        $secondsCheck = $this->getConfigData('seconds_active', null, $order->getPayment()->getMethodInstance()->getCode());
        if (isset($secondsCheck)) {
            $secondsActive = $secondsCheck;
            $daysActive = ""; //unset days_active if seconds_active is set
        } else {
            $secondsActive = "";
        }

        $use_base_currency = $this->getMainConfigData('transaction_currency');

        $checkoutData = $this->getCheckoutData($order, $use_base_currency);
        $shoppingCart = $checkoutData["shopping_cart"];
        $checkoutData = $checkoutData["checkout_options"];

        $currency = $this->_mspHelper->getCurrencyCode($order, $use_base_currency);

        $addressData = $this->parseCustomerAddress($billing->getStreetLine(1), $billing->getStreetLine(2));
        $street = $addressData['address'];
        $housenumber = $addressData['housenumber'];

        if ($billing->getTelephone() == '-') {
            $phone = '';
        } else {
            $phone = $billing->getTelephone();
        }



        //Shipping
        if ($order->canShip()) {
            $shippingaddressData = $this->parseCustomerAddress($shipping->getStreetLine(1), $shipping->getStreetLine(2));
            $shipping_street = $shippingaddressData['address'];
            $shipping_housenumber = $shippingaddressData['housenumber'];

            if ($shipping->getTelephone() == '-') {
                $shipping_phone = '';
            } else {
                $shipping_phone = $shipping->getTelephone();
            }

            $delivery_data = [
                "first_name" => $shipping->getFirstName(),
                "last_name" => $shipping->getLastName(),
                "address1" => $shipping_street,
                "house_number" => $shipping_housenumber,
                "zip_code" => trim($shipping->getPostcode()),
                "city" => $shipping->getCity(),
                "state" => $shipping->getRegionCode(),
                "country" => $shipping->getCountryId(),
                "phone" => $shipping_phone,
                "email" => $order->getCustomerEmail()
            ];
        } else {
            $delivery_data = [];
        }


        if (!empty($this->issuer_id) || $this->_gatewayCode == "BANKTRANS"
            || $this->_gatewayCode == "EINVOICE" || !is_null($recurring)
        ) {
            $type = 'direct';
        } else {
            $type = 'redirect';
        }

        /** @var \Magento\Framework\Locale\Resolver $resolver */
        $resolver =$this->_localeResolver;

        if ($this->_manualGateway != null) {
            $this->_gatewayCode = $this->_manualGateway;
        }

        if ($resetGateway && $this->_isAdmin) {
            $this->_gatewayCode = "";
        }

        $hash = $this->_mspHelper->encryptOrder($order->getIncrementId());

        $this->url->setRedirectUrl('multisafepay/connect/success', ['hash' => $hash])
            ->setCancelUrl('multisafepay/connect/cancel', ['hash' => $hash]);
        
        $store_id = $order->getStoreId();

        if ($this->_isAdmin) {
            $this->url
                ->setNotificationUrl('multisafepay/connect/notification', ['type' => 'initial'], $store_id)
                ->setCancelUrl('multisafepay/connect/cancel', ['hash' => $hash], $store_id)
                ->setRedirectUrl('multisafepay/connect/success', ['hash' => $hash], $store_id);
        }

        $notification = $this->url->getNotificationUrl();
        $redirecturl = $this->url->getRedirectUrl();
        $cancelurl = $this->url->getCancelUrl();

        $customerID = $this->_customerSession->getCustomer()->getId();

        if ($this->canSaveCreditCardToken($params)) {
            /** @var MultisafepayTokenization $model */
            $model = $this->_mspToken->create();
            $model->addData([
                    "customer_id" => $this->_customerSession->getCustomer()->getId(),
                    "recurring_hash" => $this->_mspHelper->getUniqueHash(),
                    "order_id" => $order->getIncrementId(),
                    "cc_type" => $this->_gatewayCode,
                    "name" => trim($params['name']) === '' ? null : $params['name'],
                ]);
            $model->save();
        }

        if (!is_null($customerID)
            && $this->_mspHelper->isEnabled('tokenization')
            && isset($params['save'])
            && isset($params['recurring_hash'])
            && filter_var($params['save'], FILTER_VALIDATE_BOOLEAN)
            && (in_array($params['recurring_hash'], $this->_creditcards->tokenizationSupported()) || empty($params['recurring_hash']))
        ) {

            $model = $this->_mspToken->create();
            $model->addData(
                [
                    "customer_id"    => $customerID,
                    "recurring_hash" => $this->_mspHelper->getUniqueHash(),
                    "order_id" => $order->getIncrementId(),
                    "cc_type" => $this->_gatewayCode,
                    "name" => (!empty($params['name']) && $params['name'] != "") ? $params['name'] : null,
                ]
            );
            $model->save();
        }

        $ip_address = $this->validateIP($order->getRemoteIp());
        $forwarded_ip = $this->validateIP($order->getXForwardedFor());

        try {
            $mspOrderData = [
                "type" => $type,
                "order_id" => $order->getIncrementId(),
                "recurring_id" => (!empty($recurring)) ? $this->_mspHelper->decrypt($recurring['recurring_id']) : "",
                "currency" => $currency,
                "amount" => $this->_mspHelper->getAmountInCents($order, $use_base_currency),
                "description" => __('Order')." #{$order->getIncrementId()} ". __('@') ." {$this->_mspHelper->getStoreName()}",
                "var1" => "",
                "var2" => "",
                "var3" => "",
                "items" => $items,
                "manual" => "false",
                "gateway" => $this->_gatewayCode,
                "days_active" => $daysActive,
                "seconds_active" => $secondsActive,
                "second_chance" => [
                    "send_email" => $this->_isAdmin ? false : true
                ],
                "payment_options" => [
                    "notification_url" => $notification,
                    "redirect_url" => $redirecturl,
                    "cancel_url" => $cancelurl,
                    "close_window" => "true"
                ],
                "customer" => [
                    "locale" => $resolver->emulate($store_id),
                    "ip_address" => $ip_address,
                    "forwarded_ip" => $forwarded_ip,
                    "first_name" => $billing->getFirstName(),
                    "last_name" => $billing->getLastName(),
                    "address1" => $street,
                    "house_number" => $housenumber,
                    "zip_code" => trim($billing->getPostcode()),
                    "city" => $billing->getCity(),
                    "state" => $billing->getRegionCode(),
                    "country" => $billing->getCountryId(),
                    "phone" => $phone,
                    "email" => $order->getCustomerEmail(),
                ],
                "delivery" => $delivery_data,
                "plugin" => [
                    "shop" => $magentoInfo->getName() . ' ' . $magentoInfo->getVersion() . ' ' . $magentoInfo->getEdition(),
                    "shop_version" => $magentoInfo->getVersion(),
                    "plugin_version" => ' - Plugin 1.12.1',
                    "partner" => "MultiSafepay",
                ],
                "gateway_info" => [
                    "issuer_id" => !empty($this->issuer_id) ? $this->issuer_id : null,
                ],
                "shopping_cart" => $shoppingCart,
                "checkout_options" => $checkoutData,
            ];

            $mspOrderDataObject = $this->dataObjectFactory->create();
            $mspOrderDataObject->setData('orderData', $mspOrderData);

            $this->_eventManager->dispatch(
                'before_send_msp_transaction_request',
                ['order' => $order, 'mspOrderData' => $mspOrderDataObject]
            );

            $mspOrderData = $mspOrderDataObject->getData('orderData');
            $this->_client->orders->post($mspOrderData);

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return false;
        }

        if ($this->_gatewayCode != "BANKTRANS") {
            $order->addStatusToHistory($order->getStatus(), "User redirected to MultiSafepay" . '<br/>' . "Payment link:" . '<br/>' . htmlspecialchars($this->_client->orders->getPaymentLink()), false);
            $order->getPayment()->setAdditionalInformation('payment_link', $this->_client->orders->getPaymentLink());
            $order->save();
        } else {
            $order->addStatusToHistory($order->getStatus(), "Bank transfer transaction started, waiting for payment", false);
            $order->save();
            $this->banktransurl = $this->_urlBuilder->getUrl('multisafepay/connect/success', [
                '_nosid' => true,
                '_query' => [
                    'transactionid' => $order->getIncrementId(),
                    'hash' => $this->_mspHelper->encryptOrder($order->getIncrementId())
                ]
            ]);
        }

        return $this->_client->orders;
    }

    public function validateIP($ip)
    {
        $ipList = explode(',', $ip);
        $ip = trim(reset($ipList));

        $isValid = filter_var($ip, FILTER_VALIDATE_IP);
        if ($isValid) {
            return $isValid;
        } else {
            return null;
        }
    }

    public function getIssuers()
    {
        $environment = $this->getMainConfigData('msp_env');

        $api_key = null;

        if ($environment == true) {
            $this->_client->setApiKey($this->getMainConfigData('test_api_key'));
            $api_key = $this->getMainConfigData('test_api_key');
            $this->_client->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $this->_client->setApiKey($this->getMainConfigData('live_api_key'));
            $api_key = $this->getMainConfigData('live_api_key');
            $this->_client->setApiUrl('https://api.multisafepay.com/v1/json/');
        }

        if (empty($api_key)) {
            return false;
        }

        try {
            $issuers = $this->_client->issuers->get();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return false;
        }
        return $issuers;
    }

    public function shipOrder($order)
    {
        $payment = $order->getPayment();
        $transaction_id = $payment->getLastTransId();
        $transactionRepository = $this->_transactionRepositoryInterface;
        $transaction = $transactionRepository->getByTransactionId($transaction_id, $payment->getId(), $order->getId());

        if ($transaction == null) {
            return true;
        }

        $transaction_details = $transaction->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);

        $shipped = [];
        $shipped['success'] = false;
        $shipped['error'] = false;
        $payment = $order->getPayment()->getMethodInstance();

        //Check if the payment method is a MultiSafepay method. If its a MultiSafepay method then the payment object has a _gatewayCode property. So if it doesn't exist then return true to stop MultiSafepay shipment update but continue Magento shipment process.
        if (!property_exists($payment, '_gatewayCode')) {
            return true;
        }

        $environment = $this->getMainConfigData('msp_env');
        $this->initializeClient($environment, $order);


        if ($this->_mspHelper->isFastcheckoutTransaction($transaction_details)) {
            $id = $order->getQuoteId();
        } else {
            $id = $order->getIncrementId();
        }
        $params = $this->_requestHttp->getParams();

        $tracking_number = "";

        if (isset($params['tracking'])) {
            foreach ($params['tracking'] as $tracking) {
                $tracking_number = $tracking['number'];
            }
        }

        $endpoint = 'orders/' . $id;
        $this->_client->orders->patch(
            [
                "tracktrace_code" => $tracking_number,
                "carrier" => $order->getShippingDescription(),
                "ship_date" => date('Y-m-d H:i:s'),
                "reason" => 'Shipped'
            ],
            $endpoint
        );

        if (!empty($this->_client->orders->success)) {
            $shipped['success'] = true;
        } else {
            $shipped['error'] = true;
        }
        return $shipped;
    }

    /**
     * @param Order $order
     * @param $use_base_currency
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCheckoutData(Order $order, $use_base_currency)
    {
        $alternateTaxRates = [];
        $shoppingCart = [];
        $items = $order->getAllItems();

        foreach ($items as $item) {
            $product_id = $item->getProductId();

            foreach ($order->getAllItems() as $order_item) {
                $order_product_id = $order_item->getProductId();
                if ($order_product_id == $product_id) {
                    $quantity = (string)floatval($item->getQtyOrdered());
                }
            }

            if ($item->getParentItem()) {
                continue;
            }
            $taxClass = ($item->getTaxPercent() == 0 ? 'none' : $item->getTaxPercent());
            $rate = $item->getTaxPercent() / 100;

            if ($taxClass == 'none') {
                $rate = '0.00';
            }

            $alternateTaxRates['tax_tables']['alternate'][] = [
                "standalone" => "true",
                "name" => $taxClass,
                "rules" => [
                    ["rate" => $rate]
                ],
            ];

            // name and options
            $itemName = $item->getName();
            $options = $this->getProductOptions($item);
            if (!empty($options)) {
                $optionString = '';
                foreach ($options as $option) {
                    $optionString = $option['label'] . ": " . $option['print_value'] . ",";
                }
                $optionString = substr($optionString, 0, -1);

                $itemName .= ' (';
                $itemName .= $optionString;
                $itemName .= ')';
            }

            $ndata = $item->getData();

            if ($ndata['price'] != 0) {

                $storeId = $this->getStore();
                if ($use_base_currency) {
                    $price = $ndata['base_price'] - ($item->getBaseDiscountAmount() / $quantity);

                    // Fix for 1027 with catalog prices including tax
                    if ($this->_scopeConfig->getValue(
                        'tax/calculation/price_includes_tax',
                        ScopeInterface::SCOPE_STORE,
                        $storeId
                    )) {
                        $price = (($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount()) /
                            $quantity / (1 + ($item->getTaxPercent() / 100)));
                        $price = round($price, 10);
                    }
                } else {
                    $price = $ndata['price'] - ($item->getDiscountAmount() / $quantity);

                    // Fix for 1027 with catalog prices including tax
                    if ($this->_scopeConfig->getValue(
                        'tax/calculation/price_includes_tax',
                        ScopeInterface::SCOPE_STORE,
                        $storeId
                    )) {
                        $price = (($item->getRowTotalInclTax() - $item->getDiscountAmount()) /
                            $quantity / (1 + ($item->getTaxPercent() / 100)));
                        $price = round($price, 10);
                    }
                }

                /*
                 * We should process FPT within the cart data.
                 */
                if ($item->getWeeeTaxApplied()) {
                    $weeeTax = json_decode($item->getWeeeTaxApplied());
                    foreach ($weeeTax as $tax) {
                        if (isset($tax->base_amount)) {
                            if ($use_base_currency) {
                                $amount = $tax->base_amount;
                            } else {
                                $amount = $tax->amount;
                            }

                            if (!$this->_scopeConfig->getValue('tax/weee/apply_vat', ScopeInterface::SCOPE_STORE, $storeId)) {
                                $weetaxClass = 'BTW0';
                                $alternateTaxRates['tax_tables']['alternate'][] = [
                                    "standalone" => "true",
                                    "name" => "BTW0",
                                    "rules" => [
                                        ["rate" => "0.00"]
                                    ],
                                ];
                            } else {
                                $weetaxClass = $taxClass;
                            }

                            $shoppingCart['shopping_cart']['items'][] = [
                                "name" => $tax->title,
                                "description" => $tax->title,
                                "unit_price" => $amount,
                                "quantity" => $quantity,
                                "merchant_item_id" => 'FPT',
                                "tax_table_selector" => $weetaxClass,
                                "weight" => [
                                    "unit" => "KG",
                                    "value" => "0",
                                ]
                            ];
                        }
                    }
                }



                $shoppingCart['shopping_cart']['items'][] = [
                    "name" => $itemName,
                    "description" => $item->getDescription(),
                    "unit_price" => $price,
                    "quantity" => $quantity,
                    "merchant_item_id" => $item->getId(),
                    "tax_table_selector" => $taxClass,
                    "weight" => [
                        "unit" => "KG",
                        "value" => $item->getWeight(),
                    ]
                ];
            }
        }

        //Add shipping line item
        $title = $order->getShippingDescription();

        if ($use_base_currency) {
            //Code blow added to recalculate excluding tax for the shipping cost. Older Magento installations round differently, causing a 1 cent mismatch. This is why we recalculate it.
            $diff = $order->getBaseShippingInclTax() - $order->getBaseShippingAmount();
            if ($order->getBaseShippingAmount() > 0) {
                $cost = ($diff / $order->getBaseShippingAmount()) * 100;
            } else {
                $cost = $diff * 100;
            }
            $shipping_percentage = 1 + round($cost, 0) / 100;
            $shippin_exc_tac_calculated = ($order->getBaseShippingInclTax() - ($order->getBaseShippingDiscountAmount() * $shipping_percentage)) / $shipping_percentage;
            $shipping_percentage = 0 + round($cost, 0) / 100;
            $shipping_cost_orig = $order->getBaseShippingAmount();
        } else {
            //Code blow added to recalculate excluding tax for the shipping cost. Older Magento installations round differently, causing a 1 cent mismatch. This is why we recalculate it.
            $diff = $order->getShippingInclTax() - $order->getShippingAmount();
            if ($order->getShippingAmount() > 0) {
                $cost = ($diff / $order->getShippingAmount()) * 100;
            } else {
                $cost = $diff * 100;
            }
            $shipping_percentage = 1 + round($cost, 0) / 100;
            $shippin_exc_tac_calculated = ($order->getShippingInclTax() - ($order->getShippingDiscountAmount() * $shipping_percentage)) / $shipping_percentage;
            $shipping_percentage = 0 + round($cost, 0) / 100;
            $shipping_cost_orig = $order->getShippingAmount();
        }



        if ($shipping_percentage == 1 || $shipping_cost_orig == 0) {
            $shipping_percentage = "0.00";
        }

        if ($shipping_percentage == '0') {
            $shipping_percentage = "0.00";
        }

        $price = $shippin_exc_tac_calculated;
        $alternateTaxRates['tax_tables']['alternate'][] = [
            "standalone" => "true",
            "name" => $shipping_percentage,
            "rules" => [
                ["rate" => $shipping_percentage]
            ],
        ];


        $shoppingCart['shopping_cart']['items'][] = [
            "name" => $title,
            "description" => 'Shipping',
            "unit_price" => $price,
            "quantity" => "1",
            "merchant_item_id" => 'msp-shipping',
            "tax_table_selector" => $shipping_percentage,
            "weight" => [
                "unit" => "KG",
                "value" => "0",
            ]
        ];

        /*
         * Start support for custom totals
         */
        $storeId = $this->getStore();
        $path = 'multisafepay/connect/advanced/custom_totals';
        $customTotalConfig = $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        $customTotalList = array_map('trim', explode(';', $customTotalConfig));

        $excludeTotals = [
            self::KEY_SUB_TOTAL,
            self::KEY_SHIPPING,
            self::KEY_TAX,
            self::KEY_GRAND_TOTAL
        ];
        $excludeTotals = array_merge($excludeTotals, $customTotalList);
        $totals = $this->_checkoutSession->getQuote()->getTotals();

        foreach ($totals as $total) {
            $customTotalPrice = $total->getData('value');
            if (empty($customTotalPrice)) {
                continue;
            }

            $customTotalCode = $total->getCode();
            if (in_array($customTotalCode, $excludeTotals, true)) {
                continue;
            }

            $customTotalTitle = $total->getData('title');
            $customTotalTax = 'CustomTotalTax_' . $customTotalCode;

            $shoppingCart['shopping_cart']['items'][] = [
                'name' => $customTotalTitle,
                'description' => $customTotalTitle,
                'unit_price' => $customTotalPrice,
                'quantity' => '1',
                'merchant_item_id' => $customTotalCode,
                'tax_table_selector' => $customTotalTax,
                'weight' => [
                    'unit' => 'KG',
                    'value' => '0',
                ]
            ];
            $alternateTaxRates['tax_tables']['alternate'][] = [
                'standalone' => 'true',
                'name' => $customTotalTax,
                'rules' => [
                    ['rate' => '0.00']]
            ];
        }

        /*
         * Start Payment fee support for official MultiSafepay payment fee extension
         */
        if ($order->getPaymentFee()) {
            if ($use_base_currency) {
                $payment_fee = $order->getBasePaymentFee();
            } else {
                $payment_fee = $order->getPaymentFee();
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $fee_title = $objectManager->create('MultiSafepay\PaymentFee\Helper\Data')->_getMethodDescription($order->getPayment()->getMethod());
            $shoppingCart['shopping_cart']['items'][] = [
                "name" => $fee_title,
                "description" => $fee_title,
                "unit_price" => $payment_fee,
                "quantity" => "1",
                "merchant_item_id" => 'payment-fee',
                "tax_table_selector" => '0.00',
                "weight" => [
                    "unit" => "KG",
                    "value" => "0",
                ]
            ];
        } else {
            /*
             * Start Fooman Surcharge support
             */
            /* We don't process fooman fee's for backend and rest api created orders */
            $app_state = $this->_appState;
            $area_code = $app_state->getAreaCode();

            $allowedAreas = [
                Area::AREA_ADMINHTML,
                Area::AREA_WEBAPI_REST,
            ];
            if (defined('\Magento\Framework\App\Area::AREA_GRAPHQL')) {
                $allowedAreas[] = Area::AREA_GRAPHQL;
            }
            if (!in_array($area_code, $allowedAreas)) {
                $orderRepository = $this->_orderRepositoryInterface;
                $order = $orderRepository->get($order->getId());

                $extensionAttributes = $order->getExtensionAttributes();
                if ($extensionAttributes) {
                    if (method_exists($extensionAttributes, 'getFoomanTotalGroup')) {
                        $orderTotalGroup = $extensionAttributes->getFoomanTotalGroup();
                        if ($orderTotalGroup) {
                            $items = $orderTotalGroup->getItems();
                            if (!empty($items)) {
                                foreach ($items as $total) {
                                    if ($total->getBaseTaxAmount() > 0) {
                                        $percentage = round(($total->getBaseTaxAmount() / $total->getBaseAmount()), 2);
                                    } else {
                                        $percentage = "0.00";
                                    }

                                    $shoppingCart['shopping_cart']['items'][] = [
                                        "name" => $total->getLabel(),
                                        "description" => $total->getLabel(),
                                        "unit_price" => $total->getBaseAmount(),
                                        "quantity" => "1",
                                        "merchant_item_id" => 'payment-fee',
                                        "tax_table_selector" => $percentage,
                                        "weight" => [
                                            "unit" => "KG",
                                            "value" => "0",
                                        ]
                                    ];

                                    $alternateTaxRates['tax_tables']['alternate'][] = [
                                        "standalone" => "true",
                                        "name" => $percentage,
                                        "rules" => [
                                            ["rate" => $percentage]
                                        ],
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }


        $checkoutData["shopping_cart"] = $shoppingCart['shopping_cart'];
        $checkoutData["checkout_options"] = $alternateTaxRates;

        return $checkoutData;
    }

    public function notification($order, $success = false, $fetch = false)
    {
        $params = $this->_requestHttp->getParams();
        $environment = $this->getMainConfigData('msp_env');

        $this->initializeClient($environment, $order);

        if (isset($params['transactionid'])) {
            $transactionid = $params['transactionid'];
        }

        if (empty($transactionid)) {
            $payment = $order->getPayment();
            $int_transaction_id = $payment->getLastTransId();
            $transactionRepository = $this->_transactionRepositoryInterface;
            $transaction = $transactionRepository->getByTransactionId($int_transaction_id, $payment->getId(), $order->getId());
            $transaction_details = $transaction->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);

            if ($this->_mspHelper->isFastcheckoutTransaction($transaction_details)) {
                $transactionid = $order->getQuoteId();
            } else {
                $transactionid = $order->getIncrementId();
            }
        }


        $msporder = $this->_client->orders->get($endpoint = 'orders', $transactionid, $body = [], $query_string = false);

        //$this->logger->info(print_r($msporder, true));
        //Avoid errors shown to consumer when there was an error on requesting the transaction status
        if ($success && !$this->_client->orders->success) {
            return true;
        } elseif (!$this->_client->orders->success) {
            return false;
        }

        $status = $msporder->status;

        $customerID = $order->getCustomerId();

        if (!is_null($customerID)
            && $this->_mspHelper->isEnabled('tokenization')
            && isset($msporder->payment_details->recurring_id)
            && isset($msporder->payment_details->last4)
            && isset($msporder->payment_details->card_expiry_date)
        ) {
            $id = $this->_mspHelper->getRecurringIdByOrderId(
                $order->getIncrementId()
            );
            if (!empty($id)) {

                $customerRecurringIds
                    = $this->_mspHelper->getRecurringIdsByCustomerId(
                        $customerID,
                        true
                    );

                $lastElm = end($customerRecurringIds);

                foreach ($customerRecurringIds as $customerRecurringId) {

                    $recurring = $this->_mspToken->create()->load(
                        $customerRecurringId
                    );

                    if ($this->_mspHelper->decrypt($recurring['recurring_id'])
                        !==
                        $msporder->payment_details->recurring_id
                    ) {
                        if ($customerRecurringId === $lastElm) {
                            $model = $this->_mspToken->create()->load($id);

                            $model->setData(
                                "recurring_id",
                                $this->_mspHelper->encrypt(
                                    $msporder->payment_details->recurring_id
                                )
                            );
                            $model->setData(
                                "last_4",
                                $msporder->payment_details->last4
                            );
                            $model->setData(
                                "expiry_date",
                                $msporder->payment_details->card_expiry_date
                            );
                            $model->setData(
                                "cc_type",
                                $msporder->payment_details->type
                            );


                            $model->save();
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        $this->_mspHelper->setMultisafepayStatus($order, $status);

        /**
         *    Start undo cancel function
         */
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_CANCELED && $status == \MultiSafepay\Connect\Helper\Data::MSP_COMPLETED) {
            $this->undoCancel->execute($order);
        }

        $payment = $order->getPayment();

        /**
         *    Update paymentmethod if paid with other payment method
         */
        if (isset($msporder->payment_details)) {
            $msp_gateway = $msporder->payment_details->type;
            $gatewayCode = $payment->getMethodInstance()->_gatewayCode;
            if ($gatewayCode != $msp_gateway) {
                $new_gateway_code = $this->_mspHelper->getPaymentCode($msp_gateway);
                if ($new_gateway_code) {
                    $payment->setMethod($new_gateway_code);
                    $payment_change_comment = 'MultiSafepay: payment method changed from ' . $this->_mspHelper->getPaymentCode($gatewayCode) . ' to ' . $new_gateway_code;
                    $order->addStatusHistoryComment($payment_change_comment, false);
                    $order->save();
                }
            }
        }

        $order_email = $this->getMainConfigData('send_order_email');
        if (($order_email == "after_transaction" && $status != "initialized" && $status != "expired" && !$order->getEmailSent()) ||
            ($payment->getMethodInstance()->getCode() == 'mspbanktransfer' && !$order->getEmailSent())
            /* || ($status == "expired" && isset($this->_client->orders->data->transaction_id)) *///PLGMAGTWO-106.
        ) {
            $this->_orderNotifier->notify($order);
        }

        /**
         *    ENDING UNDO CANCEL CODE
         */
        switch ($status) {
            case \MultiSafepay\Connect\Helper\Data::MSP_INIT:
                //We don't process this callback as the status would be the same as the new order status configured.
                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_COMPLETED:
                $order_email = $this->getMainConfigData('send_order_email');

                if ($order_email == "after_transaction_paid" && !$order->getEmailSent()) {
                    $this->_orderNotifier->notify($order);
                }

                $this->_registerPaymentCapture(true, $transactionid, $order, $msporder);

                if ($fetch) {
                    return true;
                }

                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_UNCLEARED:
                if ($fetch) {
                    return false;
                }
                $this->_registerPaymentPending($transactionid, $order, $msporder);
                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_VOID:
                if ($fetch) {
                    return false;
                }
                $cancelled = $this->getMainConfigData('cancelled_order_status');
                if ($cancelled == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $order->registerCancellation('<b>Transaction voided</b><br />')->save();
                } else {
                    $order->setStatus($cancelled)->save();
                }
                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_DECLINED:
                if ($fetch) {
                    return false;
                }
                $declined = $this->getMainConfigData('declined_order_status');
                if ($declined == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $order->registerCancellation('<b>Transaction declined</b><br />')->save();
                } else {
                    $order->setStatus($declined)->save();
                }
                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_EXPIRED:
                if ($fetch) {
                    return false;
                }
                $expired = $this->getMainConfigData('expired_order_status');
                if ($expired == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $order->registerCancellation('<b>Transaction voided</b><br />')->save();
                } else {
                    $order->setStatus($expired)->save();
                }
                //$order->registerCancellation('<b>Transaction expired</b><br />')->save();
                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_CANCELLED:
                if ($fetch) {
                    return false;
                }
                $cancelled = $this->getMainConfigData('cancelled_order_status');
                if ($cancelled == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $order->registerCancellation('<b>Transaction voided</b><br />')->save();
                } else {
                    $order->setStatus($cancelled)->save();
                }
                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_CHARGEBACK:
                if ($fetch) {
                    return false;
                }
                $chargeback = $this->getMainConfigData('chargeback_order_status');
                $order->setStatus($chargeback)->save();
                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_REFUNDED:
                //We don't process this callback as refunds are done using the Magento Backoffice now
                break;
            case \MultiSafepay\Connect\Helper\Data::MSP_PARTIAL_REFUNDED:
                //We don't process this callback as refunds are done using the Magento Backoffice now
                break;
            default:
                return false;
        }

        if (!$fetch) {
            return true;
        }
    }

    /**
     * Process payment pending notification
     *
     * @return void
     * @throws Exception
     */
    public function _registerPaymentPending($transactionid, $order, $msporder)
    {
        if ($order->getPayment()->getMethodInstance()->_gatewayCode != 'SANTANDER') {
            $order->addStatusToHistory($order->getStatus(), "<b>Uncleared Transaction you can accept the transaction manually within MultiSafepay Control</b><br />", false)->save();
        }
    }

    /**
     * Process completed payment (either full or partial)
     *
     * @param bool $skipFraudDetection
     * @param string $transactionid
     * @param Order $order
     * @param \stdClass $msporder
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _registerPaymentCapture($skipFraudDetection, $transactionid, $order, $msporder)
    {
        if (($order->canInvoice() || ($order->getStatus() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT && $msporder->status == \MultiSafepay\Connect\Helper\Data::MSP_COMPLETED)) || ($order->getStatus() == \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW && $msporder->status == \MultiSafepay\Connect\Helper\Data::MSP_COMPLETED)) {
            $payment = $order->getPayment();
            $payment->setTransactionId($msporder->transaction_id);

            //NOTICE: There is an issue with Magento lower than 2.1.8 causing issues creating an invoice when not using the base currency
            //https://github.com/magento/magento2/commit/c0c24116c3a790db671ae1831c09a4e51adf0549
            //Set to the order base currency because of issue described above
            $payment->setCurrencyCode($order->getBaseCurrencyCode());
            $payment->setPreparedMessage('<b>MultiSafepay status: ' . $msporder->status . '</b><br />');
            $payment->setParentTransactionId($msporder->transaction_id);
            $payment->setShouldCloseParentTransaction(false);
            $payment->setIsTransactionClosed(0);

            $payment->registerCaptureNotification($order->getBaseTotalDue(), $skipFraudDetection && $msporder->transaction_id);
            $payment->setIsTransactionApproved(true);
            $payment->save();

            if ((float)$order->getBaseGrandTotal() != ($msporder->amount / 100)) {
                $MspCurrency  = $this->_currencyFactory->create()->load($msporder->currency);
                $order->addStatusToHistory(
                    $order->getStatus(),
                    __(
                        "Notice: Captured amount %1 differs from MultiSafepay amount %2",
                        $order->getBaseCurrency()->formatTxt($order->getBaseGrandTotal()),
                        $MspCurrency->formatTxt((float)$msporder->amount / 100)
                    ),
                    false
                );
            }

            $transdetails = [];
            $transdetails['Fastcheckout'] = $msporder->fastcheckout;
            $transaction = $payment->addTransaction('capture', null, true, 'multisafepay');
            $transaction->setParentTxnId($msporder->transaction_id);
            $transaction->setIsClosed(1);
            $transaction->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $transdetails);
            $transaction->save();

            if (isset($msporder->payment_methods[1])) {
                $line = $this->_mspHelper->createMultiPaymentMethodsLine($msporder->payment_methods);
                $order->addStatusToHistory($order->getStatus(), $line);
            }

            // Force order to Processing to solve https://github.com/magento/magento2/issues/18148
            $state = Order::STATE_PROCESSING;
            $status = $this->_statusResolver->getOrderStatusByState($order, $state);
            $order->setState($state);
            $order->setStatus($status);

            $this->_orderRepositoryInterface->save($order);

            //We get the created invoice and send the invoice id to MultiSafepay so it can be added to financial exports
            $environment = $this->getMainConfigData('msp_env');
            $this->initializeClient($environment, $order);

            foreach ($order->getInvoiceCollection() as $invoice) {
                if ($invoice->getOrderId() == $order->getEntityId()) {
                    $endpoint = 'orders/' . $transactionid;

                    try {
                        $neworder = $this->_client->orders->patch(
                            [
                                "invoice_id" => $invoice->getIncrementId(),
                            ],
                            $endpoint
                        );

                        if (!empty($this->_client->orders->result->error_code)) {
                            throw new \Magento\Framework\Exception\LocalizedException(__("Error " . htmlspecialchars($this->_client->orders->result->error_code)));
                        }
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        throw new \Magento\Framework\Exception\LocalizedException(__("Error " . htmlspecialchars($e->getMessage())));
                    }
                }
                $emailInvoice = $this->getMainConfigData('email_invoice');
                $gateway = $payment->getMethodInstance()->_gatewayCode;


                if ($emailInvoice && $gateway != 'PAYAFTER' && $gateway != 'KLARNA' && $gateway != 'AFTERPAY') {
                    $this->_invoiceSender->send($invoice, true);
                }/* elseif (($gateway == 'PAYAFTER' || $gateway == 'KLARNA') && $send_bno_invoice && $emailInvoice) {
                  $this->_invoiceSender->send($invoice, true);
                  } */
            }
        }
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote == null) {
            $quote = $this->_checkoutSession->getQuote();
        }
        //Check amount restrictions
        if ($quote &&
            (
                $quote->getBaseGrandTotal() < $this->_minAmount ||
                (
                    $this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount
                )
            )) {
            return false;
        }

        //Don't show payment method based on main configuration settings
        if ($this->getCode() == 'connect') {
            return false;
        }

        //Check currency restrictions
        $allowedCurrencies = explode(',', $this->getConfigData('allowed_currency'));
        if (!in_array($quote->getQuoteCurrencyCode(), $allowedCurrencies)) {
            return false;
        }

        //Don't show payment method if group is not allowed
        if (!$this->restrictions->isGroupAllowed(
            $quote->getCustomerGroupId(),
            $this->getConfigData('allowed_groups')
        )) {
            return false;
        }

        return parent::isAvailable($quote) && $this->isCarrierAllowed(
            $quote->getShippingAddress()->getShippingMethod()
        );
    }

    /**
     * Refund
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //Prevent 0 refunds (full refund)
        if ($amount <= 0) {
            return $this;
        }

        $transaction_id = $payment->getParentTransactionId();
        $order = $payment->getOrder();
        $transactionRepository = $this->_transactionRepositoryInterface;
        $transaction = $transactionRepository->getByTransactionId($transaction_id, $payment->getId(), $order->getId());
        $transaction_details = $transaction->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);

        if ($this->_mspHelper->isFastcheckoutTransaction($transaction_details)) {
            $endpoint = 'orders/' . $order->getQuoteId() . '/refunds';
            $id = $order->getQuoteId();
        } else {
            $endpoint = 'orders/' . $order->getIncrementId() . '/refunds';
            $id = $order->getIncrementId();
        }

        $gateway = $payment->getMethodInstance()->_gatewayCode;
        $environment = $this->getMainConfigData('msp_env');
        $this->initializeClient($environment, $order);

        if ($gateway == 'PAYAFTER' || $gateway == 'KLARNA' || $gateway == 'EINVOICE' || $gateway == 'AFTERPAY') {
            //Get the creditmemo data as this is not yet stored at this moment.
            $data = $this->_requestHttp->getPost('creditmemo');
            //Do a status request for this order to receive already refunded item data from MSP transaction
            $msporder = $this->_client->orders->get('orders', $id, $body = [], $query_string = false);
            $originalCart = $msporder->shopping_cart;
            $refundData = [];

            foreach ($originalCart->items as $key => $item) {
                if ($item->unit_price > 0) {
                    $refundData['checkout_data']['items'][] = $item;
                }
                foreach ($order->getCreditmemosCollection() as $creditmemo) {
                    foreach ($creditmemo->getAllItems() as $product) {
                        $product_id = $product->getData('order_item_id');
                        if ($product_id == $item->merchant_item_id) {
                            $qty_refunded = $product->getData('qty');
                            if ($qty_refunded > 0) {
                                if ($item->unit_price > 0) {
                                    $refundItem = new \stdclass();
                                    $refundItem->name = $item->name;
                                    $refundItem->description = $item->description;
                                    if ($this->hasMinusSign($item->unit_price)) {
                                        $refundItem->unit_price = $item->unit_price;
                                    } else {
                                        $refundItem->unit_price = 0 - $item->unit_price;
                                    }
                                    $refundItem->quantity = $qty_refunded;
                                    $refundItem->merchant_item_id = $item->merchant_item_id;
                                    $refundItem->tax_table_selector = $item->tax_table_selector;
                                    $refundData['checkout_data']['items'][] = $refundItem;
                                }
                            }
                        }
                    }
                }

                foreach ($data['items'] as $productid => $proddata) {
                    if ($item->merchant_item_id == $productid) {
                        if ($proddata['qty'] > 0) {
                            if ($item->unit_price > 0) {
                                $refundItem = new \stdclass();
                                $refundItem->name = $item->name;
                                $refundItem->description = $item->description;
                                $refundItem->unit_price = 0 - $item->unit_price;
                                $refundItem->quantity = $proddata['qty'];
                                $refundItem->merchant_item_id = $item->merchant_item_id;
                                $refundItem->tax_table_selector = $item->tax_table_selector;
                                $refundData['checkout_data']['items'][] = $refundItem;
                            }
                        }
                    }
                }

                //The complete shipping cost is refunded also so we can remove it from the checkout data and refund it
                if ($item->merchant_item_id == 'msp-shipping') {
                    $storeId = $this->getStore();
                    $taxSalesDisplayShipping = $this->_scopeConfig->getValue('tax/sales_display/shipping', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
                    if ($data['shipping_amount'] == $order->getShippingAmount() || ($taxSalesDisplayShipping == \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX && $data['shipping_amount'] == $order->getShippingInclTax())) {
                        $refundItem = new \stdclass();
                        $refundItem->name = $item->name;
                        $refundItem->description = $item->description;
                        if ($this->hasMinusSign($item->unit_price)) {
                            $refundItem->unit_price = $item->unit_price;
                        } else {
                            $refundItem->unit_price = 0 - $item->unit_price;
                        }
                        $refundItem->quantity = '1';
                        $refundItem->merchant_item_id = $item->merchant_item_id;
                        $refundItem->tax_table_selector = $item->tax_table_selector;
                        $refundData['checkout_data']['items'][] = $refundItem;
                    } else {
                        if ($data['shipping_amount'] != 0) {
                            throw new \Magento\Framework\Exception\LocalizedException(__("Error: Refund not processed online as it did not match the complete shipping cost.  "));
                            $order->addStatusHistoryComment('MultiSafepay: Refund not processed online as it did not match the complete shipping cost.', false);
                            $order->save();
                            return $this;
                        }
                    }
                }

                if ($item->merchant_item_id == 'FPT') {
                    $refundItem = new \stdclass();
                    $refundItem->name = $item->name;
                    $refundItem->description = $item->description;
                    if ($this->hasMinusSign($item->unit_price)) {
                        $refundItem->unit_price = $item->unit_price;
                    } else {
                        $refundItem->unit_price = 0 - $item->unit_price;
                    }
                    $refundItem->quantity = '1';
                    $refundItem->merchant_item_id = $item->merchant_item_id;
                    $refundItem->tax_table_selector = $item->tax_table_selector;
                    $refundData['checkout_data']['items'][] = $refundItem;
                }

                if ($item->name == $order->getShippingDescription() && $item->unit_price < 0) {
                    $refundItem = new \stdclass();
                    $refundItem->name = $item->name;
                    $refundItem->description = $item->description;
                    if ($this->hasMinusSign($item->unit_price)) {
                        $refundItem->unit_price = $item->unit_price;
                    } else {
                        $refundItem->unit_price = 0 - $item->unit_price;
                    }
                    $refundItem->quantity = '1';
                    $refundItem->merchant_item_id = $item->merchant_item_id;
                    $refundItem->tax_table_selector = $item->tax_table_selector;
                    $refundData['checkout_data']['items'][] = $refundItem;
                }
            }

            // Calculate adjustments
            $adjustmentLines = $this->refundHelper->getAdjustmentOrderLines($data, $order);
            foreach ($adjustmentLines as $adjustmentLine) {
                $refundData['checkout_data']['items'][] = $adjustmentLine;
            }
        } else {
            /*
             * Because we support transactions based on base- and storeview currency, we must check if we use the correct amount to refund i.c.m. with the correct currency
             *
             */
            $use_base_currency = $this->getMainConfigData('transaction_currency');
            if ($use_base_currency) {
                $refund_amount = $amount;
                $currency = $order->getBaseCurrencyCode();
            } else {
                $refund_amount = $amount * $order->getBaseToOrderRate();
                $currency = $order->getOrderCurrencyCode();
            }

            $refundData = [
                "amount" => $refund_amount * 100,
                "currency" => $currency,
                "description" => "Refund: " . $id,
            ];
        }

        try {
            $msporder = $this->_client->orders->post($refundData, $endpoint);
            if (!empty($this->_client->orders->result->error_code)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(htmlspecialchars("Error: " . $this->_client->orders->result->error_code) . ": " . htmlspecialchars($this->_client->orders->result->error_info)));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__(htmlspecialchars($e->getMessage())));
        }
        return $this;
    }

    protected function hasMinusSign($value)
    {
        return (substr(strval($value), 0, 1) == "-");
    }

    /**
     * Initialize the MSP API Client
     * @param boolean $environment
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    public function initializeClient($environment, $order)
    {
        if ($environment == true) {
            $this->_client->setApiKey($this->getConfigData('test_api_key', $order->getStoreId(), $order->getPayment()->getMethodInstance()->getCode()));
            $this->_client->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $this->_client->setApiKey($this->getConfigData('live_api_key', $order->getStoreId(), $order->getPayment()->getMethodInstance()->getCode()));
            $this->_client->setApiUrl('https://api.multisafepay.com/v1/json/');
        }
    }

    /**
     * Set order state and status ofter placing order and before redirect to MultiSafepay
     * First status will be pending payment, orders with this status are not visible yet for the consumer
     *
     * @param string $paymentAction
     * @param \Magento\Framework\Object $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        /*
         * Should the order confirmation email be submitted after placing the order?
         */
        $order_email = $this->getMainConfigData('send_order_email');
        if ($order_email != "place_order") {
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false);
        }

        $status = $this->getNewOrderStatus();

        $stateObject->setState($this->_mspHelper->getAssignedState($status));
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);
    }

    public function getNewOrderStatus()
    {
        return $this->getMainConfigData('order_status');
    }

    //Instructions will be visible within the order/e-mails
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    /**
     * Check whether payment method can be used with selected shipping method
     *
     * @param string $shippingMethod
     * @return bool
     */
    public function isCarrierAllowed($shippingMethod)
    {
        if ($this->getConfigData('allowed_carrier_active') == true) {
            if (empty($shippingMethod)) {
                return true;
            }
            return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
        }
        return true;
    }

    /**
     * Fetch transaction details info
     *
     * Update transaction info if there is one placing transaction only
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return boolean
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {
        $order = $payment->getOrder();
        if ($this->notification($order, false, true)) {
            $payment->setIsTransactionApproved(true);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve information from gateway/giftcard configuration
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null, $code = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }

        if (null === $storeId) {
            $storeId = $this->getStore();
        }

        if (null === $code) {
            $code = $this->getCode();
        }

        $mspType = $this->_mspHelper->getPaymentType($code);
        $path = $mspType . '/' . $code . '/' . $field;


        if ($field == "test_api_key" || $field == "live_api_key") {
            return $this->getMainConfigData($field, $storeId);
        }
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Retrieve information from multisafepay configuration
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     */
    public function getMainConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }

        if (null === $storeId) {
            $storeId = $this->getStore();
        }

        $path = 'multisafepay/connect/' . $field;
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getGlobalConfig($path, $storeId = null)
    {

        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function parseCustomerAddress($street_address1, $street_address2)
    {
        list($address, $apartment) = $this->parseAddress($street_address1, $street_address2);
        $customer['address'] = $address;
        $customer['housenumber'] = $apartment;
        return $customer;
    }

    /**
     * Parses and splits up an address in street and housenumber
     *
     * @param string $address1
     * @param string $address2
     *
     * @return array
     */
    public function parseAddress($address1, $address2)
    {
        // Trim the addresses
        $address1 = trim($address1);
        $address2 = trim($address2);
        $fullAddress = trim("{$address1} {$address2}");
        $fullAddress = preg_replace("/[[:blank:]]+/", " ", $fullAddress);

        // Make array of all regex matches
        $matches = [];

        /**
         * Regex part one: Add all before number.
         * If number contains whitespace, Add it also to street.
         * All after that will be added to apartment
         */
        $pattern = '/(.+?)\s?([\d]+[\S]*)(\s?[A-z]*?)$/';
        preg_match($pattern, $fullAddress, $matches);

        //Save the street and apartment and trim the result
        $street = isset($matches[1]) ? $matches[1] : '';
        $apartment = isset($matches[2]) ? $matches[2] : '';
        $extension = isset($matches[3]) ? $matches[3] : '';
        $street = trim($street);
        $apartment = trim($apartment . $extension);

        return [$street, $apartment];
    }

    // From http://www.php.net/manual/en/function.strrpos.php#78556
    public function rstrpos($haystack, $needle, $offset = null)
    {
        $size = strlen($haystack);

        if (is_null($offset)) {
            $offset = $size;
        }

        $pos = strpos(strrev($haystack), strrev($needle), $size - $offset);

        if ($pos === false) {
            return false;
        }

        return $size - $pos - strlen($needle);
    }

    public function canSaveCreditCardToken(array $params): bool
    {
        if ($this->_gatewayCode !== 'CREDITCARD') {
            return false;
        }

        if (!isset($params['save'], $params['name'])) {
            return false;
        }

        if (!(bool)$params['save']) {
            return false;
        }

        if (!$this->_mspHelper->isEnabled('tokenization')) {
            return false;
        }

        if ($this->_customerSession->getCustomer()->getId() === null) {
            return false;
        }

        return true;
    }
}
