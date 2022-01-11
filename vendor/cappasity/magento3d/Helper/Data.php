<?php

namespace CappasityTech\Magento3D\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use CappasityTech\Magento3D\Model\ParamsRuleInterface;
use CappasityTech\Magento3D\Model\PaymentPlanInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CALL_BACK_URL = "cappasitytech/result/syncjobresultsave";
    const SAVE_JOB_DEFAULT_PARAMS = "cappasitytech/general/savejobparams";
    const BASE_THUMBNAIL = "images/thumbnail-3D.png";
    const DEFAULT_PARAMS = [
        'autoRun' => 1,
        'closeButton' => 0,
        'logo' => 1,
        'autoRotate' => 0,
        'autoRotateTime' => 10,
        'autoRotateDelay' => 2,
        'autoRotateDir' => 1,
        'hideFullScreen' => 1,
        'hideAutoRotateOpt' => 1,
        'hideSettingsBtn' => 0,
        'enableImageZoom' => 1,
        'zoomQuality' => 1,
        'hideZoomOpt' => 0,
        'uiPadX' => 0,
        'uiPadY' => 0,
    ];
    const THUMBNAIL_PARAMS = [
        'small' => [
            'height' => 165,
            'width' => 165,
            'crop' => 'fit'
        ],
        'medium' => [
            'height' => 250,
            'width' => 250,
            'crop' => 'fit'
        ]
    ];

    protected $scopeConfig;
    private $date;
    private $settingFactory;
    private $store;
    private $productCollectionFactory;
    private $productVisibility;
    private $productStatus;
    private $paymentPlan;
    private $syncJobParams;
    private $currentPlan = null;
    private $currentPlanAlias = null;
    private $currentPlanLevel = null;
    private $playerSettings = null;
    private $image3dParams = null;
    private $image3dEmbedRenderer;
    private $image3dGenerator;
    private $activeUser = null;
    private $currentAlias = null;
    private $cappasityParamsRuleFactory;

    protected static $_instance;

    public function __construct(
        Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CappasityTech\Magento3D\Model\SettingFactory $settingFactory,
        \CappasityTech\Magento3D\Model\SyncJobParams $syncJobParams,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \CappasityTech\Magento3D\Model\ParamsRuleFactory $cappasityParamsRuleFactory,
        \CappasityTech\Magento3D\Model\PaymentPlan $paymentPlan
    ) {
        parent::__construct($context);

        $this->productCollectionFactory = $productFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->paymentPlan = $paymentPlan;
        $this->syncJobParams = $syncJobParams;
        $this->settingFactory = $settingFactory;
        $this->store = $storeManager;
        $this->date = $timezone;
        $this->scopeConfig = $context->getScopeConfig();
        $this->cappasityParamsRuleFactory = $cappasityParamsRuleFactory;

        $this->image3dEmbedRenderer = \CappasitySDK\EmbedRendererFactory::getRendererInstance();
        $generatorFactory = new \CappasitySDK\PreviewImageSrcGeneratorFactory;
        $this->image3dGenerator = $generatorFactory::getGeneratorInstance();

        self::$_instance = $this;
    }

    public static function getInstance()
    {
        if (!self::$_instance instanceof \Magento\Framework\App\Helper\AbstractHelper) {
            throw new \RuntimeException('Unable to initialize the data');
        }
        return self::$_instance;
    }

    public function getBaseThumbnail()
    {
        return self::BASE_THUMBNAIL;
    }

    public function getCurrentDate()
    {
        date_default_timezone_set(
            $this->date->getConfigTimezone('store', $this->store->getStore())
        );
        $date = $this->date->date();
        return $date->format('Y/m/d H:i:s');
    }

    public function getActiveUser()
    {
        if ($this->activeUser === null) {
            $this->activeUser = $this->settingFactory->create()->loadActiveUser();
        }
        return $this->activeUser;
    }

    public function resetData()
    {
        $this->currentPlan = null;
        $this->currentPlanAlias = null;
        $this->currentPlanLevel = null;
        $this->playerSettings = null;
        $this->image3dParams = null;
        $this->activeUser = null;
        $this->currentAlias = null;
    }

    public function getCurrentAlias()
    {
        if ($this->currentAlias === null) {
            $this->currentAlias = $this->getActiveUser()->getAliases();
        }
        return $this->currentAlias;
    }

    public function getProductsData()
    {
        $collection = $this->productCollectionFactory->create();
        if ($collection->getSize() > 0) {
            return $collection->getItems();
        }
        return false;
    }

    public function getSyncConfigValue($index)
    {
        //auto_sync_new_product,dont_sync_manual,use_thumbnail_of_button,add_preview_to_gallery,set_preview_base
        if ($user = $this->getActiveUser()) {
            return $this->syncJobParams->loadByUserId($user->getId())->getData($index);
        }
        return false;
    }

    public function getProductsCollection($productIds = [], $fromProductId = false)
    {
        $collection = $this->productCollectionFactory->create();

        if ($this->getSyncConfigValue('dont_sync_manual')) {
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'from_picker', 'neq' => '1'],
                    ['attribute' => 'from_picker', 'null' => true],
                ],
                null,
                'left'
            );
        }
        if ($productIds) {
            $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);
        }
        if ($fromProductId) {
            $collection->addAttributeToFilter('entity_id', ['gt' => $fromProductId]);
        }
        $collection->addAttributeToFilter('sku', ['regexp' => '^[.0-9A-Za-z_\-]{1,50}$']);
        $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $collection->getSelect()->joinLeft(
            ['cappasity' => $collection->getTable('cappasity_tech_magento3D_sync_data')],
            'e.entity_id = cappasity.product_id',
            ['capp' => 'cappasity.orig_image_3d']
        );

        return $collection;
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue($field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getDefaultSaveJobParams($storeId = null)
    {
        $result = $this->getConfigValue(self::SAVE_JOB_DEFAULT_PARAMS, $storeId);
        return json_decode($result, true);
    }

    public function getBaseUrl()
    {
        return $this->store->getStore()->getBaseUrl();
    }

    public function getCallBackUrl()
    {
        return $this->getBaseUrl() . self::CALL_BACK_URL;
    }

    public function getCurrentPlan()
    {
        if ($this->currentPlan === null) {
            if ($this->getActiveUser() && $this->getActiveUser()->getToken()) {
                $client = \CappasitySDK\ClientFactory::getClientInstance(
                    ['apiToken' => $this->getActiveUser()->getToken()]
                );


                try {
                    $response = $client
                        ->getUser(new \CappasitySDK\Client\Model\Request\Users\MeGet())
                        ->getBodyData();

                    $user = $response
                        ->getData()
                        ->getAttributes();

                    $userPlanId = $user->getPlan();

                    $response = $client
                        ->getPaymentsPlan(\CappasitySDK\Client\Model\Request\Payments\Plans\PlanGet::fromData($userPlanId))
                        ->getBodyData();

                    $plan = $response
                        ->getData()
                        ->getAttributes();
                } catch (\Exception $e) {
                    $plan = null;
                }

                $this->currentPlan = $plan;
            }
        }
        return $this->currentPlan;
    }

    public function getCurrentPlanLevel()
    {
        if ($this->currentPlanLevel === null && $plan = $this->getCurrentPlan()) {
            $this->currentPlanLevel = $plan->getLevel();
        }
        return $this->currentPlanLevel;
    }

    public function getCurrentPlanAlias()
    {
        if ($this->currentPlanAlias === null && $plan = $this->getCurrentPlan()) {
            $this->currentPlanAlias = $plan->getAlias();
        }
        return $this->currentPlanAlias;
    }

    public function getStatusesByPlanLevel()
    {
        $statuses = [];
        $settings = $this->getPlayerSettings();

        if (count($settings)) {
            foreach ($settings as $setting) {
                $statuses[$setting['name']] = $setting['status'];
            }
        }
        return $statuses;
    }

    public function getPlayerSettings()
    {
        if ($this->playerSettings === null) {
            $planLevel = $this->getCurrentPlanLevel();
            $modelParams = $this->cappasityParamsRuleFactory->create();
            $settingsCollection = $modelParams->getCollection();

            $result = [];
            if (count($settingsCollection)) {
                foreach ($settingsCollection as $item) {
                    $data = $item->getData();
                    $data['status'] = $planLevel >= $item->getReqPlanLevel() ? 'enabled' : 'disabled';
                    $result[] = $data;
                }
            }
            $this->playerSettings = $result;
        }
        return $this->playerSettings;
    }

    public function getImage3dParams()
    {
        if ($this->image3dParams === null) {
            $this->image3dParams = self::DEFAULT_PARAMS;
            $playerSettings = $this->getPlayerSettings();
            foreach ($playerSettings as $playerSetting) {
                $value = $playerSetting['value'];
                if ($playerSetting['type'] == 'boolean') {
                    $value = boolval($value);
                }
                if (in_array($playerSetting['name'], ['autoRotateTime', 'autoRotateDelay', 'uiPadX', 'uiPadY'])) {
                    $value = intval($value);
                }
                $this->image3dParams[$playerSetting['name']] = $value;
            }
        }
        return $this->image3dParams;
    }

    public function renderImage3d($id)
    {
        $params = $this->getImage3dParams();
        $params['height'] = '100%';
        $params['width'] = '100%';
        $params['viewId'] = $id;
        return $this->image3dEmbedRenderer->render($params);
    }

    public function generatePreviewImageSrc($id, $type = null)
    {
        $params = ['format' => \CappasitySDK\PreviewImageSrcGenerator::FORMAT_JPG];
        if ($type) {
            $params['modifiers'] = self::THUMBNAIL_PARAMS[$type];
        }
        $alias = $this->getCurrentAlias();
        return $this->image3dGenerator->generatePreviewImageSrc($alias, $id, $params);
    }

    public function getPaidSettingCodes()
    {
        $codes = [];
        foreach (json_decode($this->getRuleSaveData(), true) as $item) {
            if ($item['paid'] === true) {
                $codes[] = $item['code'];
            }
        }
        return $codes;
    }

    public function getRuleSaveData()
    {
        $result = [
            [
                'code' => 'auto_sync_new_product',
                'label' => 'Automatically synchronize new/updated products',
                'default_value' => true,
                'paid' => false
            ],
            [
                'code' => 'dont_sync_manual',
                'label' => 'Don\'t sync manual choices',
                'default_value' => true,
                'paid' => false
            ],
            [
                'code' => 'use_thumbnail_of_button',
                'label' => 'Use thumbnail instead of 3D button',
                'default_value' => true,
                'paid' => true
            ],
            [
                'code' => 'add_preview_to_gallery',
                'label' => 'Add preview image to gallery',
                'default_value' => true,
                'paid' => true
            ],
            [
                'code' => 'set_preview_base',
                'label' => 'Set Preview Image as Base',
                'default_value' => false,
                'paid' => true
            ],
        ];
        return json_encode($result);
    }

    public function getSampleDataPaymentLabel()
    {
        $sampleData = [
            [
                PaymentPlanInterface::DATA_VALUE => 1,
                PaymentPlanInterface::DATA_LABEL => "free",
            ],
            [
                PaymentPlanInterface::DATA_VALUE => 10,
                PaymentPlanInterface::DATA_LABEL => "lite",
            ],
            [
                PaymentPlanInterface::DATA_VALUE => 20,
                PaymentPlanInterface::DATA_LABEL => "basic",
            ],
            [
                PaymentPlanInterface::DATA_VALUE => 30,
                PaymentPlanInterface::DATA_LABEL => "professional",
            ],

        ];
        return $sampleData;
    }

    public function getSampleDataRule()
    {
        $sampleData = [
            [
                ParamsRuleInterface::DATA_LABEL => 'autoRun',
                ParamsRuleInterface::DATA_NAME => 'autoRun',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '1',
                ParamsRuleInterface::DATA_VALUE => '1',
                ParamsRuleInterface::DATA_PAID => false,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 0,
                ParamsRuleInterface::DATA_DESCRIPTION =>
                    'Start the player (widget) automatically or display the preview and play button closeButton',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'closeButton',
                ParamsRuleInterface::DATA_NAME => 'closeButton',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '0',
                ParamsRuleInterface::DATA_VALUE => '0',
                ParamsRuleInterface::DATA_PAID => false,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 0,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Show close button',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'logo',
                ParamsRuleInterface::DATA_NAME => 'logo',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '1',
                ParamsRuleInterface::DATA_VALUE => '1',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 20,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Show Cappasity logo',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'analytics',
                ParamsRuleInterface::DATA_NAME => 'analytics',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '1',
                ParamsRuleInterface::DATA_VALUE => '1',
                ParamsRuleInterface::DATA_PAID => false,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 0,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Enable analytics',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'autoRotate',
                ParamsRuleInterface::DATA_NAME => 'autoRotate',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '0',
                ParamsRuleInterface::DATA_VALUE => '0',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Start automatic rotation',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'autoRotateTime',
                ParamsRuleInterface::DATA_NAME => 'autoRotateTime',
                ParamsRuleInterface::DATA_TYPE => 'float',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '10.0',
                ParamsRuleInterface::DATA_VALUE => '10.0',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Rotation time of the full turn, seconds',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'autoRotateDelay',
                ParamsRuleInterface::DATA_NAME => 'autoRotateDelay',
                ParamsRuleInterface::DATA_TYPE => 'float',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '2.0',
                ParamsRuleInterface::DATA_VALUE => '2.0',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Delay if rotation was interrupted, seconds',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'autoRotateDir',
                ParamsRuleInterface::DATA_NAME => 'autoRotateDir',
                ParamsRuleInterface::DATA_TYPE => 'float',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '1.0',
                ParamsRuleInterface::DATA_VALUE => '1.0',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION =>
                    'Autorotate direction (clockwise is 1, counter-clockwise is -1)',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'hideFullScreen',
                ParamsRuleInterface::DATA_NAME => 'hideFullScreen',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '0',
                ParamsRuleInterface::DATA_VALUE => '0',
                ParamsRuleInterface::DATA_PAID => false,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 0,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Hide fullscreen view button',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'hideAutoRotateOpt',
                ParamsRuleInterface::DATA_NAME => 'hideAutoRotateOpt',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '0',
                ParamsRuleInterface::DATA_VALUE => '0',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Hide autorotate button',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'hideSettingsBtn',
                ParamsRuleInterface::DATA_NAME => 'hideSettingsBtn',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '0',
                ParamsRuleInterface::DATA_VALUE => '0',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Hide settings button',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'enableImageZoom',
                ParamsRuleInterface::DATA_NAME => 'enableImageZoom',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '1',
                ParamsRuleInterface::DATA_VALUE => '1',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Enable zoom',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'zoomQuality',
                ParamsRuleInterface::DATA_NAME => 'zoomQuality',
                ParamsRuleInterface::DATA_TYPE => 'integer',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => 1,
                ParamsRuleInterface::DATA_VALUE => 1,
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Zoom quality (SD is 1, HD is 2)',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'hideZoomOpt',
                ParamsRuleInterface::DATA_NAME => 'hideZoomOpt',
                ParamsRuleInterface::DATA_TYPE => 'boolean',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => '0',
                ParamsRuleInterface::DATA_VALUE => '0',
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Hide zoom button',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'uiPadX',
                ParamsRuleInterface::DATA_NAME => 'uiPadX',
                ParamsRuleInterface::DATA_TYPE => 'integer',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => 0,
                ParamsRuleInterface::DATA_VALUE => 0,
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Horizontal (left, right) padding for player UI in pixels',
            ],
            [
                ParamsRuleInterface::DATA_LABEL => 'uiPadY',
                ParamsRuleInterface::DATA_NAME => 'uiPadY',
                ParamsRuleInterface::DATA_TYPE => 'integer',
                ParamsRuleInterface::DATA_DEFAULT_VALUE => 0,
                ParamsRuleInterface::DATA_VALUE => 0,
                ParamsRuleInterface::DATA_PAID => true,
                ParamsRuleInterface::DATA_REG_PLAN_LEVEL => 30,
                ParamsRuleInterface::DATA_DESCRIPTION => 'Vertical (top, bottom) padding for player UI in pixels',
            ],

        ];
        return $sampleData;
    }
}
