<?php

namespace CappasityTech\Magento3D\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;

class InstallData implements InstallDataInterface
{
    const USER_GUIDE_URL = 'https://cappasity.com';

    private $_configInterface;
    private $helper;
    private $eavSetupFactory;

    public function __construct(
        \Magento\Framework\App\State $state,
        \CappasityTech\Magento3D\Helper\Data $helper,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->_configInterface = $configInterface;
        $this->helper = $helper;
        $this->eavSetupFactory = $eavSetupFactory;
        try {
            $state->getAreaCode();
        } catch (\Exception $e) {
            $state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        }
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //add default value save job params
        $defaultData = [
            'auto_sync_new_product' => 1,
            'dont_sync_manual' => 0,
            'use_thumbnail_of_button' => 0,
            'add_preview_to_gallery' => 0,
            'set_preview_base' => 0,
        ];
        $defaultData = json_encode($defaultData, true);

        $this->_configInterface->saveConfig('cappasitytech/general/savejobparams', $defaultData, 'default', 0);

        // add url userguide
        $this->_configInterface->saveConfig('cappasitytech/general/userguide_url', self::USER_GUIDE_URL, 'default', 0);

        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '0.0.1', '<')) {
            $select = $setup->getConnection()->select()
                ->from(['c' => $setup->getTable('cappasity_tech_magento3D_payment_plan')]);
            $result = $setup->getConnection()->fetchAll($select);
            if (!$result) {
                // Add data label payment plan
                $sampleData = $this->helper->getSampleDataPaymentLabel();

                foreach ($sampleData as $sampleDataItem) {
                    $setup->getConnection()
                        ->insert($setup->getTable('cappasity_tech_magento3D_payment_plan'), $sampleDataItem);
                }
            }
            $select = $setup->getConnection()
                ->select()->from(['c' => $setup->getTable('cappasity_tech_magento3D_params_rule')]);
            $result = $setup->getConnection()->fetchAll($select);
            if (!$result) {
                // Add data label params rule
                $sampleData = $this->helper->getSampleDataRule();
                foreach ($sampleData as $sampleDataItem) {
                    $setup->getConnection()
                        ->insert($setup->getTable('cappasity_tech_magento3D_params_rule'), $sampleDataItem);
                }
            }

            $ruleSave = $this->helper->getRuleSaveData();

            $this->_configInterface->saveConfig('cappasitytech/general/rule_save', $ruleSave, 'default', 0);


            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'image3d',
                [
                    'group' => 'image3d',
                    'type' => 'text',
                    'backend' => 'CappasityTech\Magento3D\Model\Product\Attribute\Backend\Image3D',
                    'frontend' => 'CappasityTech\Magento3D\Model\Product\Attribute\Frontend\Image3D',
                    'label' => '3d Image',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => false,
                    'required' => false,
                    'user_defined' => false,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'from_picker',
                [
                    'group' => 'image3d',
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'From Picker',
                    'input' => 'boolean',
                    'class' => '',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => '0',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );
        }
        $installer->endSetup();
    }
}
