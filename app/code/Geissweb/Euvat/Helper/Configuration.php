<?php
/**
 * ||GEISSWEB| EU VAT Enhanced
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GEISSWEB End User License Agreement
 * that is available through the world-wide-web at this URL: https://www.geissweb.de/legal-information/eula
 *
 * DISCLAIMER
 *
 * Do not edit this file if you wish to update the extension in the future. If you wish to customize the extension
 * for your needs please refer to our support for more information.
 *
 * @copyright   Copyright (c) 2015 GEISS Weblösungen (https://www.geissweb.de)
 * @license     https://www.geissweb.de/legal-information/eula GEISSWEB End User License Agreement
 */

namespace Geissweb\Euvat\Helper;

use Geissweb\Euvat\Logger\Logger;
use Geissweb\Euvat\Registry\CurrentAdminOrderStoreId;
use Magento\Backend\Model\UrlInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Manager;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\UrlInterface as FrameworkUrlInterface;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Configuration
 * Provides a handy wrapper for getting config values by store
 */
class Configuration
{
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var ProductMetadataInterface
     */
    public $productMetaData;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var GroupManagementInterface
     */
    public $groupManagement;

    /**
     * @var CollectionFactory
     */
    public $countryCollectionFactory;

    /**
     * @var Http
     */
    public $request;

    /**
     * @var UrlInterface
     */
    public $backendUrlInterface;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var mixed
     */
    public $unserializer;

    /**
     * @var Dir
     */
    public $moduleDir;

    /**
     * @var \Magento\Framework\Filesystem\DriverInterface
     */
    public $filesystemDriver;

    /**
     * @var Geissweb\Euvat\Registry\CurrentAdminOrderStoreId
     */
    public $currentAdminOrderStoreId;

    /**
     * @var State
     */
    public $appState;

    /**
     * @var ResourceInterface
     */
    private $moduleResource;

    /**
     * @var Config
     */
    private $configResourceModel;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var FrameworkUrlInterface
     */
    private $url;

    /**
     * Configuration constructor.
     *
     * @param Http                       $request
     * @param ScopeConfigInterface       $scopeConfig
     * @param ProductMetadataInterface   $productMetadata
     * @param Manager                    $moduleManager
     * @param ResourceInterface          $moduleResource
     * @param StoreManagerInterface      $storeManager
     * @param Config                     $configResourceModel
     * @param GroupManagementInterface   $groupManagement
     * @param UrlInterface               $backendUrl
     * @param FrameworkUrlInterface      $url
     * @param Dir                        $moduleDir
     * @param File                       $filesystemDriver
     * @param State                      $appState
     * @param Logger                     $logger
     * @param Compat\UnserializerFactory $unserializerFactory
     * @param CurrentAdminOrderStoreId   $currentAdminOrderStoreId
     */
    public function __construct(
        Http $request,
        ScopeConfigInterface $scopeConfig,
        ProductMetadataInterface $productMetadata,
        Manager $moduleManager,
        ResourceInterface $moduleResource,
        StoreManagerInterface $storeManager,
        Config $configResourceModel,
        GroupManagementInterface $groupManagement,
        UrlInterface $backendUrl,
        FrameworkUrlInterface $url,
        Dir $moduleDir,
        File $filesystemDriver,
        State $appState,
        Logger $logger,
        Compat\UnserializerFactory $unserializerFactory,
        CurrentAdminOrderStoreId $currentAdminOrderStoreId
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->productMetaData = $productMetadata;
        $this->storeManager = $storeManager;
        $this->groupManagement = $groupManagement;
        $this->request = $request;
        $this->moduleManager = $moduleManager;
        $this->moduleResource = $moduleResource;
        $this->configResourceModel = $configResourceModel;
        $this->backendUrlInterface = $backendUrl;
        $this->moduleDir = $moduleDir;
        $this->filesystemDriver = $filesystemDriver;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->unserializer = $unserializerFactory->create();
        $this->currentAdminOrderStoreId = $currentAdminOrderStoreId;
        $this->url = $url;
    }

    /**
     * Shorthand get config value by store
     *
     * @param string $configPath
     *
     * @return mixed
     */
    public function getConfig($configPath)
    {
        try {
            $params = $this->request->getParams();

            if (isset($params['store_id'])) {
                $value = $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $params['store_id']);
            } elseif ($this->appState->getAreaCode() === Area::AREA_ADMINHTML) {
                $currentAdminStore = $this->currentAdminOrderStoreId->get();
                $value = $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $currentAdminStore);
            } else {
                $value = $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);
            }

            /* //Enable when needed, to also log config values
            if(!is_object($value)) {
                if(isset($params['store_id'])) {
                    $currentStore = $this->storeManager->getStore($params['store_id']);
                } elseif( $this->appState->getAreaCode() === Area::AREA_ADMINHTML ){
                    $currentStore = $this->storeManager->getStore($currentAdminStore);
                } else {
                    $currentStore = $this->storeManager->getStore();
                }
                $this->logger->debug('Getting '.$configPath.' value for store: '.$currentStore->getName()." (".$currentStore->getCode()." from website id ".$currentStore->getWebsiteId().")");
                $this->logger->debug('Value is: '.json_encode($value));
            }
            */
            return $value;
        } catch (NoSuchEntityException $e) {
            $this->logger->warning($e->getMessage());
        } catch (LocalizedException $e) {
            $this->logger->warning($e->getMessage());
        }
    }

    /**
     * Is debug mode enabled?
     * @return bool
     */
    public function isDebugEnabled()
    {
        return (bool)$this->getConfig('euvat/mod_info/debug');
    }

    /* ----------- BREXIT ------------- */

    /**
     * @return bool
     */
    public function isUkThresholdEnabled()
    {
        return (bool)$this->getConfig('euvat/brexit_settings/threshold_enabled');
    }

    /**
     * @return float
     */
    public function getUkThresholdValue()
    {
        return (float)$this->getConfig('euvat/brexit_settings/threshold_value');
    }

    /* ----------- FRONTEND FIELD CONFIGS ------------- */

    /**
     * @return int
     */
    public function getFieldValidationDelay()
    {
        $delay = (int)$this->getConfig('euvat/integration/field_delay');
        if (empty($delay)) {
            $delay = 0;
        }
        return $delay;
    }

    /**
     * @return bool
     */
    public function getAskCustomerForCountryCorrection()
    {
        return (bool)$this->getConfig('euvat/integration/ask_customer_country_correction');
    }

    /**
     * Get VAT number input field JS component config
     * @param array $baseConfig
     * @param null  $customScope
     *
     * @return array
     */
    public function getVatFieldConfig($baseConfig = [], $customScope = null)
    {
        $fieldVisible = (bool)$this->getConfig('customer/create_account/vat_frontend_visibility');

        $config = [
            'euCountries' => $this->getEuCountries(),
            'fieldVisibleCountries' => $this->getFieldVisibleCountries(),
            'taxCalcMethod' => $this->getVatBasedOn(),
            'handle' => $this->request->getFullActionName(),
            'debug' => $this->isDebugEnabled(),
            'template'=> 'Geissweb_Euvat/vatfield',
            'elementTmpl' => 'Geissweb_Euvat/vat-input',
            'vatFrontendVisibility' => $fieldVisible,
            'placeholder' => $this->getConfig('euvat/integration/field_placeholder'),
            'delay' => $this->getFieldValidationDelay(),
            'validationUrl' => $this->url->getUrl('euvat/vatnumber/validation', []),
            'askCustomerCountryCorrection' => $this->getAskCustomerForCountryCorrection(),
            'optionalRegions' => []
        ];

        if ($customScope !== null) {
            $config['customScope'] = $customScope;
        }

        if ($this->getVatFieldTooltipText() != false) {
            $config['tooltip'] = ['description' => $this->getVatFieldTooltipText()];
        }

        return array_merge($baseConfig, $config);
    }

    /**
     * @return array
     */
    public function getAdminVatFieldConfig()
    {
        $config = $this->getVatFieldConfig();
        $config['vatFrontendVisibility'] = true;
        $config['component'] = 'Geissweb_Euvat/js/form/element/vat-number-admin';
        $config['template'] = 'Geissweb_Euvat/form/vat-field';
        $config['elementTmpl'] = 'Geissweb_Euvat/form/element/vat-input';
        $config['validationUrl'] = $this->backendUrlInterface->getUrl('euvat/vatnumber/validation', []);
        $config['tooltip'] = [
            'description' => __('VAT number validation details will be set automatically after you save the customer.')
        ];
        return $config;
    }

    /**
     * @param array $baseConfig
     * @param null  $customScope
     *
     * @return array
     */
    public function getVatFieldConfigMageplaza($baseConfig = [], $customScope = null)
    {
        $config = $this->getVatFieldConfig($baseConfig, $customScope);
        $config['template'] = 'Geissweb_Euvat/vatfield-mageplaza';
        if ($this->getConfig('osc/design_configuration/page_design') == 'material') {
            $config['template'] = 'Geissweb_Euvat/vatfield-mageplaza-material';
            $config['elementTmpl'] = 'Geissweb_Euvat/vat-input-mageplaza-material';
        }
        if ($customScope !== null) {
            $config['customScope'] = $customScope;
        }
        return $config;
    }

    /**
     * @param array $baseConfig
     * @param null  $customScope
     *
     * @return array
     */
    public function getVatFieldConfigAheadworks($baseConfig = [], $customScope = null)
    {
        $config = $this->getVatFieldConfig($baseConfig, $customScope);
        $config['template'] = 'Geissweb_Euvat/vatfield-aheadworks';
        if ($customScope !== null) {
            $config['customScope'] = $customScope;
        }
        return $config;
    }

    /**
     * Tooltip text
     * @return bool|string
     */
    public function getVatFieldTooltipText()
    {
        $tooltip = $this->getConfig('euvat/integration/field_tooltip');
        if ($tooltip != '') {
            return $tooltip;
        }
        return false;
    }

    /**
     * Get VAT number input field JS component config
     * @return array
     */
    public function getFieldValidationAtRegistration()
    {
        return [
            $this->getConfig('euvat/integration/field_validation_registration') => true
        ];
    }

    /**
     * Get VAT number input field JS component config
     * @return array
     */
    public function getFieldValidationAtAddressEdit()
    {
        return [
            $this->getConfig('euvat/integration/field_validation_addressedit') => true
        ];
    }

    /**
     * Get VAT number input field JS component config
     * @return array
     */
    public function getFieldValidationAtCheckout()
    {
        return [
            $this->getConfig('euvat/integration/field_validation_checkout') => true
        ];
    }

    /**
     * Get whether field functionality is enabled
     * @return bool
     */
    public function getIsFieldFunctionalityEnabled(): bool
    {
        return (bool)$this->getConfig('euvat/integration/enable_vat_field');
    }

    /* ----------- COUNTRIES ------------- */

    /**
     * Gets a list of all current EU member states
     * @return array
     */
    public function getEuCountries()
    {
        return explode(",", $this->getConfig('general/country/eu_countries'));
    }

    /**
     * Get for which countries the field should be visible
     * @return array
     */
    public function getFieldVisibleCountries()
    {
        return explode(",", $this->getConfig('euvat/integration/visible_countries'));
    }

    /**
     * Checks if country is within EU
     * @param string $countryCode
     *
     * @return bool
     */
    public function isEuCountry($countryCode)
    {
        if (in_array($countryCode, $this->getEuCountries())) {
            return true;
        }
        return false;
    }

    /* ----------- GROUPS ------------- */

    /**
     * Get cart product price display types per customer group
     * @return array
     */
    public function getCartProductPriceDisplayTypeRules()
    {
        $return = [];
        $config = $this->getConfig('euvat/group_price_display/cart_product_price_display');
        if (is_string($config) && $config != '[]') {
            $rules = $this->unserializer->unserialize(
                $this->getConfig('euvat/group_price_display/cart_product_price_display')
            );
            foreach ($rules as $ruleId => $rule) {
                $return[(int)$rule['customer_group_id']] = (int)$rule['display_type'];
            }
        }
        return $return;
    }

    /**
     * Get catalog price display types per customer group
     * @return array
     */
    public function getCatalogPriceDisplayTypeRules()
    {
        $return = [];
        $config = $this->getConfig('euvat/group_price_display/catalog_price_display');
        if (is_string($config) && $config != '[]') {
            $rules = $this->unserializer->unserialize(
                $this->getConfig('euvat/group_price_display/catalog_price_display')
            );
            foreach ($rules as $ruleId => $rule) {
                $return[(int)$rule['customer_group_id']] = (int)$rule['display_type'];
            }
        }
        return $return;
    }

    /**
     * Get catalog price display types per customer group
     * @return array
     */
    public function getCartSubtotalPriceDisplayTypeRules()
    {
        $return = [];
        $config = $this->getConfig('euvat/group_price_display/cart_subtotal_price_display');
        if (is_string($config) && $config != '[]') {
            $rules = $this->unserializer->unserialize(
                $this->getConfig('euvat/group_price_display/cart_subtotal_price_display')
            );
            foreach ($rules as $ruleId => $rule) {
                $return[(int)$rule['customer_group_id']] = (int)$rule['display_type'];
            }
        }
        return $return;
    }

    /**
     * @return bool
     */
    public function isAssignCustomerGroupToGuestOrder()
    {
        return (bool)$this->getConfig('euvat/group_assignment/assign_customergroup_on_guest_order');
    }

    /**
     * Use group assignment?
     * @return bool
     */
    public function getUseGroupAssignment()
    {
        return (bool)$this->getConfig('euvat/group_assignment/use_group_assignment');
    }

    /**
     * Get the customer group for customers with valid VAT ID within EU
     * @return int
     */
    public function getTargetGroupEu()
    {
        return (int)$this->getConfig('euvat/group_assignment/target_group_eu');
    }

    /**
     * Get the customer group for customers with valid VAT ID within domestic country
     * @return int
     */
    public function getTargetGroupDomestic()
    {
        return (int)$this->getConfig('euvat/group_assignment/target_group_domestic');
    }

    /**
     * Get the customer group for customers outside EU
     * @return int
     */
    public function getTargetGroupOutsideEu()
    {
        return (int)$this->getConfig('euvat/group_assignment/target_group_outside');
    }

    /**
     * Get the customer group for customers with invalid VAT number
     * @return int
     */
    public function getTargetGroupInvalid()
    {
        return (int)$this->getConfig('euvat/group_assignment/target_group_invalid');
    }

    /**
     * Get the customer group for customers with technical error during VAT number validation
     * @return int
     */
    public function getTargetGroupErrors()
    {
        return (int)$this->getConfig('euvat/group_assignment/target_group_errors');
    }

    /**
     * Get excluded (for group assignment) customer groups
     * @return array
     */
    public function getExcludedGroups()
    {
        $excluded = explode(",", $this->getConfig('euvat/group_assignment/excluded_groups'));
        if (!is_array($excluded)) {
            return [$excluded];
        }
        return $excluded;
    }

    /**
     * Checks if a group is excluded from group assignment
     * @param $groupId
     *
     * @return bool
     */
    public function isExcludedGroup($groupId)
    {
        if (in_array($groupId, $this->getExcludedGroups())) {
            return true;
        }
        return false;
    }

    /**
     * Get excluded (for tax calculation) customer groups
     * @return array
     */
    public function getNoDynamicGroups()
    {
        $noDynamic = explode(",", $this->getConfig('euvat/vat_settings/fixed_taxcalc_groups'));
        if (!is_array($noDynamic)) {
            $noDynamic = [$noDynamic];
        }
        foreach ($noDynamic as $key => $value) {
            if ($value != '') {
                $noDynamic[$key] = (int)$value;
            } else {
                unset($noDynamic[$key]);
            }
        }
        return $noDynamic;
    }

    /**
     * Checks if a group is excluded from group assignment
     * @param $groupId
     *
     * @return bool
     */
    public function isNoDynamicGroup(int $groupId)
    {
        if (in_array($groupId, $this->getNoDynamicGroups(), true)
            || in_array(\Magento\Customer\Model\Group::CUST_GROUP_ALL, $this->getNoDynamicGroups(), true)
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return int
     */
    public function getTargetGroupDefault()
    {
        try {
            return (int) $this->groupManagement->getDefaultGroup($this->storeManager->getStore()->getId())->getId();
        } catch (NoSuchEntityException $e) {
            return 1;
        } catch (LocalizedException $e) {
            return 1;
        }
    }

    /**
     * @return bool
     */
    public function getEnableThresholdCountries()
    {
        return (bool)$this->getConfig('euvat/vat_settings/threshold_enabled');
    }

    /**
     * @return mixed
     */
    public function getThresholdCountries()
    {
        return explode(",", $this->getConfig('euvat/vat_settings/threshold_countries'));
    }

    /**
     * @param $countryCode
     *
     * @return bool
     */
    public function isThresholdCountry($countryCode)
    {
        if (in_array($countryCode, $this->getThresholdCountries())) {
            return true;
        }
        return false;
    }

    /**
     * @param $customerCountryCode
     *
     * @return bool
     */
    public function isAlwaysVatCountry($customerCountryCode)
    {
        $alwaysVatCountries = explode(",", $this->getConfig('euvat/vat_settings/always_vat_countries'));
        if (in_array($customerCountryCode, $alwaysVatCountries)) {
            return true;
        }
        return false;
    }

    /* ----------- TAX CLASSES ------------- */

    /**
     * Gets the tax class for consumers
     * @return int
     */
    public function getConsumerTaxClass()
    {
        return (int)$this->getConfig('euvat/vat_settings/tax_class_including_consumer');
    }

    /**
     * Gets the tax class for businesses including VAT
     * @return int
     */
    public function getBusinessTaxClass()
    {
        return (int)$this->getConfig('euvat/vat_settings/tax_class_including_business');
    }

    /**
     * Gets the tax class for businesses excluding VAT
     * @return int
     */
    public function getExcludingTaxClass()
    {
        return (int)$this->getConfig('euvat/vat_settings/tax_class_excluding_business');
    }

    /**
     * Reduced Product Tax Class ID
     * @return int
     */
    public function getReducedProductTaxClass()
    {
        return (int)$this->getConfig('euvat/shipping_vat_settings/reduced_product_class');
    }

    /**
     * Reduced Shipping Tax Class ID
     * @return int
     */
    public function getReducedShippingTaxClass()
    {
        return (int)$this->getConfig('euvat/shipping_vat_settings/reduced_shipping_class');
    }

    /**
     * Super Reduced Product Tax Class ID
     * @return int
     */
    public function getSuperReducedProductTaxClass()
    {
        return (int)$this->getConfig('euvat/shipping_vat_settings/super_reduced_product_class');
    }

    /**
     * Super Reduced Shipping Tax Class ID
     * @return int
     */
    public function getSuperReducedShippingTaxClass()
    {
        return (int)$this->getConfig('euvat/shipping_vat_settings/super_reduced_shipping_class');
    }

    /**
     * Get default product tax class ID (standard/highest rate)
     * @return int
     */
    public function getDefaultProductTaxClass()
    {
        return (int)$this->getConfig(\Magento\Tax\Helper\Data::CONFIG_DEFAULT_PRODUCT_TAX_CLASS);
    }

    /* ----------- OTHER ------------- */

    /**
     * Is dynamic VAT calculation enabled?
     * @return bool
     */
    public function getUseVatCalculation()
    {
        return (bool)$this->getConfig('euvat/vat_settings/use_vat_calculation');
    }

    /**
     * Is dynamic shipping VAT calculation enabled?
     * @return int
     */
    public function getUseDynamicShippingTaxClass()
    {
        return (int)$this->getConfig('euvat/shipping_vat_settings/use_dynamic_shipping_calculation');
    }

    /**
     * @return bool
     */
    public function isPeriodicRevalidationEnabled()
    {
        return (bool)$this->getConfig('euvat/revalidation/periodic_check_enabled');
    }

    /**
     * @return int
     */
    public function getRevalidationPeriod()
    {
        return (int)$this->getConfig('euvat/revalidation/validation_period');
    }

    /**
     * If offline validation mode is enabled
     * @return string
     */
    public function isOfflineValidationEnabled()
    {
        return (bool)$this->getConfig('euvat/interface_settings/use_offline_validation');
    }

    /**
     * @param $countryCode
     *
     * @return bool
     */
    public function isOfflineValidationCountry($countryCode)
    {
        $offlineValidationCountries = explode(
            ",",
            $this->getConfig('euvat/interface_settings/offline_validation_countries')
        );
        if (in_array($countryCode, $offlineValidationCountries)) {
            return true;
        }
        return false;
    }

    /**
     * If the VAT number validation is enabled
     * @return string
     */
    public function isValidationEnabled()
    {
        return (bool)$this->getConfig('euvat/interface_settings/validate_vatid');
    }

    /**
     * Gets the selected validation service
     * @return string
     */
    public function getValidationService()
    {
        return (string)$this->getConfig('euvat/interface_settings/interface');
    }

    /**
     * Get VAT calculation based on
     * @return string
     */
    public function getVatBasedOn()
    {
        return $this->getConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_BASED_ON);
    }

    /**
     * @return bool
     */
    public function isCbtEnabled()
    {
        return (bool)$this->getConfig('tax/calculation/cross_border_trade_enabled');
    }

    /**
     * @return bool
     */
    public function getDisableCbtForOutOfEurope()
    {
        return (bool)$this->getConfig('euvat/vat_settings/disable_cbt_noneu');
    }

    /**
     * @return bool
     */
    public function getDisableCbtForEuBusiness()
    {
        return (bool)$this->getConfig('euvat/vat_settings/disable_cbt_eub2b');
    }

    /**
     * Get merchant country code
     * Use country code from the VAT number
     * If VAT number does not contain the country prefix, return the country store setting
     *
     * @return string
     */
    public function getMerchantCountryCode()
    {
        $countryCode = $this->getConfig('euvat/vat_settings/domestic_country');
        if (!empty($countryCode)) {
            return $countryCode;
        }
        return (string)$this->getConfig(StoreInformation::XML_PATH_STORE_INFO_COUNTRY_CODE);
    }

    /**
     * Get the VAT number of merchant
     * @return string
     */
    public function getMerchantVatNumber()
    {
        $requesterVatNumber = $this->getConfig('euvat/interface_settings/requester_vat_number');
        if (!empty($requesterVatNumber)) {
            if (preg_match('/^[A-Z][A-Z]/', $requesterVatNumber) == 1) {
                $requesterVatNumber = substr($requesterVatNumber, 2, strlen($requesterVatNumber));
            }
            return $this->formatVatNumber($requesterVatNumber);
        }

        $vatNumber = (string)$this->getConfig(StoreInformation::XML_PATH_STORE_INFO_VAT_NUMBER);
        if (preg_match('/^[A-Z][A-Z]/', $vatNumber) == 1) {
            $vatNumber = substr($vatNumber, 2, strlen($vatNumber));
        }
        return $this->formatVatNumber($vatNumber);
    }

    /**
     * Formats a VAT number to the general format
     * @param $vatNumber
     *
     * @return string
     */
    public function formatVatNumber($vatNumber)
    {
        return strtoupper(preg_replace("/[^a-zA-Z0-9]+/", "", $vatNumber));
    }

    /**
     * Is IPv6 Workaround enabled?
     * @return bool
     */
    public function getIsIpv6Compat()
    {
        return (bool)$this->getConfig('euvat/interface_settings/ip_compat');
    }

    /**
     * IPv4 Address to bind soap requests on
     * @return string
     */
    public function getIPv4ToBindOn()
    {
        return $this->getConfig('euvat/interface_settings/ip_compat');
    }

    /* ----------- THIRD PARTY ------------- */

    public function getIsAmastyCheckoutEnabled()
    {
        return $this->moduleManager->isEnabled('Amasty_Checkout')
               && (bool)$this->getConfig('amasty_checkout/general/enabled');
    }

    /* ----------- SUPPORT ------------- */

    /**
     * Checks if setup was executed or skipped
     * @return bool
     */
    public function getIsInstalled()
    {
        return $this->getConfig('euvat/extension_info/is_installed');
    }

    /**
     * This information is only used for support and internal statistics.
     * Nothing is being sent to third parties and no personal data is collected.
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getSystemInformation()
    {
        $composerJsonFile = $this->moduleDir->getDir('Geissweb_Euvat') . DIRECTORY_SEPARATOR . 'composer.json';
        if ($this->filesystemDriver->isExists($composerJsonFile)) {
            $jsonContent = $this->filesystemDriver->fileGetContents($composerJsonFile);
            if ($this->unserializer instanceof \Magento\Framework\Serialize\Serializer\Json) {
                $composerJson = $this->unserializer->unserialize($jsonContent);
            } else {
                $composerJson = (array)json_decode($jsonContent);
            }
            $version = $composerJson['version'];
        } else {
            $version = $this->moduleResource->getDbVersion('Geissweb_Euvat');
        }

        $data = [
            'magento_version' => $this->productMetaData->getVersion(),
            'version' => $version,
            'server_hostname' => $this->request->getServer('SERVER_NAME'),
            'license_key' => $this->getConfig('euvat/mod_info/license_key'),
            'installation_type' => $this->getConfig('euvat/mod_info/installation_type'),
            'edition' => $this->productMetaData->getEdition(),
        ];

        return $data;
    }

    /**
     * Register Installation
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function registerInstallation()
    {
        $data = base64_encode(join(";", $this->getSystemInformation()));

        $cs = curl_init();
        curl_setopt($cs, CURLOPT_URL, 'https://www.geissweb.de/feeds/m2reg.php');
        curl_setopt($cs, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cs, CURLOPT_POST, 1);
        curl_setopt($cs, CURLOPT_POSTFIELDS, "v=2&d=$data");
        curl_setopt($cs, CURLOPT_VERBOSE, false);
        curl_setopt($cs, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($cs, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($cs);
        curl_close($cs);

        if ($response !== false) {
            $this->configResourceModel->saveConfig('euvat/extension_info/is_registered', true, 'default', 0);
            $this->configResourceModel->saveConfig(
                'euvat/extension_info/last_contact',
                date("d.m.Y", time()),
                'default',
                0
            );
        }
    }
}