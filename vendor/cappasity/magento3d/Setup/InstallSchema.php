<?php

namespace CappasityTech\Magento3D\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

use CappasityTech\Magento3D\Model\DataInterface;
use CappasityTech\Magento3D\Model\PaymentPlanInterface;
use CappasityTech\Magento3D\Model\SyncJobInterface;
use CappasityTech\Magento3D\Model\SyncDataInterface;
use CappasityTech\Magento3D\Model\ParamsRuleInterface;
use CappasityTech\Magento3D\Model\SyncJobParamsInterface;
use CappasityTech\Magento3D\Model\ImageParamsInterface;

/**
 * Class InstallSchema
 * @package Mageplaza\Blog\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('cappasity_tech_magento3D_user')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('cappasity_tech_magento3D_user'))
                ->addColumn(DataInterface::ENTITY_ID, Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ], 'Post ID')
                ->addColumn(DataInterface::DATA_TOKEN, Table::TYPE_TEXT, 255, ['nullable' => false], 'Token')
                ->addColumn(DataInterface::DATA_ALIASES, Table::TYPE_TEXT, 255, [], 'Alias')
                ->addColumn(DataInterface::DATA_PLAN, Table::TYPE_TEXT, 255, [], 'Payment Plan')
                ->addColumn(DataInterface::DATA_VIEW_PARAMS, Table::TYPE_TIMESTAMP, null, [], 'Params')
                ->addColumn(DataInterface::DATA_CREATE_AT, Table::TYPE_TIMESTAMP, null, [], 'Created At')
                ->addColumn(DataInterface::DATA_UPDATE_AT, Table::TYPE_TIMESTAMP, null, [], 'Updated At')
                ->addColumn(DataInterface::DATA_STATUS, Table::TYPE_INTEGER, null, [], 'Status')
                ->setComment('CappasityTech User Table');

            $installer->getConnection()->createTable($table);
        }
        if (!$installer->tableExists('cappasity_tech_magento3D_payment_plan')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('cappasity_tech_magento3D_payment_plan'))
                ->addColumn(PaymentPlanInterface::ENTITY_ID, Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ], 'Post ID')
                ->addColumn(
                    PaymentPlanInterface::DATA_VALUE,
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false],
                    'Payment Plan Value'
                )
                ->addColumn(PaymentPlanInterface::DATA_LABEL, Table::TYPE_TEXT, 255, [], 'Payment Plan Label')
                ->setComment('CappasityTech Payment Plan Table');

            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('cappasity_tech_magento3D_sync_job')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('cappasity_tech_magento3D_sync_job'))
                ->addColumn(SyncJobInterface::ENTITY_ID, Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ], 'Tag ID')
                ->addColumn(SyncJobInterface::DATA_JOB_ID, Table::TYPE_TEXT, 255, [], 'ID ')
                ->addColumn(SyncJobInterface::DATA_STATUS, Table::TYPE_INTEGER, null, [], 'Status')
                ->addColumn(SyncJobInterface::DATA_UPDATE_AT, Table::TYPE_TIMESTAMP, null, [], 'Updated At')
                ->addColumn(SyncJobInterface::DATA_CREATE_AT, Table::TYPE_TIMESTAMP, null, [], 'Created At')
                ->addColumn(SyncJobInterface::DATA_USER_ID, Table::TYPE_INTEGER, null, [], 'User Id')
                ->addColumn('items', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, [], 'SyncJob Items')
                ->setComment('SyncJob Table');

            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('cappasity_tech_magento3D_sync_job_params')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('cappasity_tech_magento3D_sync_job_params'))
                ->addColumn(SyncJobParamsInterface::ENTITY_ID, Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ], 'Tag ID')
                ->addColumn(SyncJobParamsInterface::DATA_SET_PREVIEW_BASE, Table::TYPE_INTEGER, null, [], 'ID ')
                ->addColumn(
                    SyncJobParamsInterface::DATA_ADD_PREVIEW_TO_GALLERY,
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Add Preview To Gallery '
                )
                ->addColumn(
                    SyncJobParamsInterface::DATA_USE_THUMBNAIL_OF_BUTTON,
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Use Thumbnail Of Button'
                )
                ->addColumn(
                    SyncJobParamsInterface::DATA_DONT_SYNC_MANUAL_CHOICES,
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Dont Sync Manual Choices'
                )
                ->addColumn(
                    SyncJobParamsInterface::DATA_AUTO_SYNC_NEW_PRODUCT,
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Auto Sync New Products'
                )
                ->addColumn(SyncJobParamsInterface::DATA_USER_ID, Table::TYPE_INTEGER, null, [], 'User Id')
                ->setComment('SyncJob Table');

            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('cappasity_tech_magento3D_image_params')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('cappasity_tech_magento3D_image_params'))
                ->addColumn(ImageParamsInterface::ENTITY_ID, Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ], 'Tag ID')
                ->addColumn(ImageParamsInterface::DATA_AUTORUN, Table::TYPE_INTEGER, null, [], 'Autorun ')
                ->addColumn(ImageParamsInterface::DATA_ANALYTICS, Table::TYPE_INTEGER, null, [], 'Analytics ')
                ->addColumn(ImageParamsInterface::DATA_CLOSEBUTTON, Table::TYPE_INTEGER, null, [], 'Close Button ')
                ->addColumn(
                    ImageParamsInterface::DATA_HIDEFULLSCREEN,
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Hide Full Screen '
                )
                ->addColumn(SyncJobParamsInterface::DATA_USER_ID, Table::TYPE_INTEGER, null, [], 'User Id')
                ->setComment('ImageData Table');

            $installer->getConnection()->createTable($table);
        }
        if (!$installer->tableExists('cappasity_tech_magento3D_sync_data')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('cappasity_tech_magento3D_sync_data'))
                ->addColumn(SyncJobInterface::ENTITY_ID, Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ], 'Tag ID')
                ->addColumn(
                    SyncDataInterface::DATA_PRODUCT_ID,
                    Table::TYPE_INTEGER,
                    11,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ],
                    'ID'
                )
                ->addColumn(SyncDataInterface::DATA_IMAGE_CODE_3D, Table::TYPE_TEXT, 255, [], 'Orig Image 3d Code')
                ->addColumn(SyncDataInterface::DATA_IMAGE_ORIG_CODE_3D, Table::TYPE_TEXT, 255, [], 'Orig Image 3d')
                ->addColumn(SyncDataInterface::DATA_SKU, Table::TYPE_TEXT, 255, [], 'Sku')
                ->addColumn(SyncDataInterface::DATA_IMAGE_URL_3D, Table::TYPE_TEXT, 255, [], 'Image 3d Url')
                ->addColumn(
                    SyncDataInterface::DATA_USE_THUMBNAIL_3D,
                    Table::TYPE_TEXT,
                    255,
                    ['default' => 'global'],
                    'Use Thumbnail 3d'
                )
                ->addColumn(SyncDataInterface::DATA_THUMBNAIL_3D, Table::TYPE_TEXT, 255, [], 'Thumbnail 3d Url')
                ->addColumn(
                    SyncDataInterface::DATA_SMALL_THUMBNAIL_3D,
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Small Thumbnail 3d Url'
                )
                ->addColumn(
                    SyncDataInterface::DATA_MEDIUM_THUMBNAIL_3D,
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Medium Thumbnail 3d Url'
                )
                ->addColumn(SyncDataInterface::DATA_JOB_ID, Table::TYPE_TEXT, 255, [], 'ID ')
                ->addColumn(SyncDataInterface::DATA_RESOURCE, Table::TYPE_TEXT, 255, [], 'Resource')
                ->addColumn(SyncDataInterface::DATA_UPDATE_AT, Table::TYPE_TIMESTAMP, null, [], 'Updated At')
                ->addColumn(SyncDataInterface::DATA_CREATE_AT, Table::TYPE_TIMESTAMP, null, [], 'Created At')
                ->addIndex(
                    $installer->getIdxName(
                        $installer->getTable('cappasity_tech_magento3D_sync_data'),
                        ['product_id'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['product_id'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'cappasity_tech_magento3D_sync_data',
                        'product_id',
                        'catalog_product_entity',
                        'entity_id'
                    ),
                    'product_id',
                    $installer->getTable('catalog_product_entity'),
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )->setComment('SyncData Table');

            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('cappasity_tech_magento3D_params_rule')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('cappasity_tech_magento3D_params_rule'))
                ->addColumn(ParamsRuleInterface::ENTITY_ID, Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true,
                    'unsigned' => true,
                ], 'Tag ID')
                ->addColumn(ParamsRuleInterface::DATA_NAME, Table::TYPE_TEXT, 55, [], 'Name')
                ->addColumn(ParamsRuleInterface::DATA_TYPE, Table::TYPE_TEXT, 55, [], 'Type')
                ->addColumn(ParamsRuleInterface::DATA_DEFAULT_VALUE, Table::TYPE_TEXT, 55, [], 'Default Value')
                ->addColumn(ParamsRuleInterface::DATA_VALUE, Table::TYPE_TEXT, 55, [], 'Value')
                ->addColumn(ParamsRuleInterface::DATA_REG_PLAN_LEVEL, Table::TYPE_INTEGER, null, [], 'Reg Plan Level')
                ->addColumn(ParamsRuleInterface::DATA_PAID, Table::TYPE_BOOLEAN, null, [], 'Paid')
                ->addColumn(ParamsRuleInterface::DATA_DESCRIPTION, Table::TYPE_TEXT, 255, [], 'Description')
                ->addColumn(ParamsRuleInterface::DATA_LABEL, Table::TYPE_TEXT, 255, [], 'Label')
                ->setComment('Cappasity Rule Params Table');

            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
