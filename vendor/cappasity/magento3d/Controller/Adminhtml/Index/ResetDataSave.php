<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Index;

class ResetDataSave extends \Magento\Backend\App\Action
{
    private $syncJob;
    private $syncData;
    private $setting;
    private $imageParams;
    private $syncJobParams;
    private $urlBuilder;
    protected $messageManager;

    /** delete table sync job  and syncProducts  */
    public function __construct(
        \CappasityTech\Magento3D\Model\SyncJobParamsFactory $cappasityTechSyncJobParams,
        \CappasityTech\Magento3D\Model\ImageParamsFactory $cappasityTechImageParams,
        \CappasityTech\Magento3D\Model\SettingFactory $cappasityTechSetting,
        \CappasityTech\Magento3D\Model\SyncJobFactory $cappasityTechSyncJob,
        \CappasityTech\Magento3D\Model\SyncDataFactory $cappasityTechSyncData,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->messageManager = $messageManager;
        $this->syncJob = $cappasityTechSyncJob;
        $this->syncData = $cappasityTechSyncData;
        $this->syncJobParams = $cappasityTechSyncJobParams;
        $this->imageParams = $cappasityTechImageParams;
        $this->setting = $cappasityTechSetting;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->clearData();
            $this->messageManager->addSuccess(__('All data was successfully deleted'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Unable to delete data-' . $e->getMessage()));
        }
        $url = $this->urlBuilder->getUrl('cappasity/index/action');
        $this->_response->setRedirect($url);
    }

    private function clearData()
    {
        $model = $this->syncJob->create();
        $this->deleteTableModel($model);

        $model = $this->syncData->create();
        $this->deleteTableModel($model);

        $model = $this->setting->create();
        $this->deleteTableModel($model);

        $model = $this->imageParams->create();
        $this->deleteTableModel($model);

        $model = $this->syncJobParams->create();
        $this->deleteTableModel($model);

        $this->resetProductAttribute($model);
    }

    private function deleteTableModel($model)
    {
        if ($model->getResource()) {
            $connection = $model->getResource()->getConnection();
            $tableName = $model->getResource()->getMainTable();
            $connection->truncateTable($tableName);
        }
    }

    private function resetProductAttribute($model)
    {
        if ($model->getResource()) {
            $connection = $model->getResource()->getConnection();
            $eavAttribute = $model->getResource()->getTable('eav_attribute');
            $catalogProductEntityInt = $model->getResource()->getTable('catalog_product_entity_int');

            $attrCode = 'from_picker';
            $select = $connection->select()
                ->from(
                    ['ea' => $eavAttribute],
                    ['attribute_id']
                )
                ->where("attribute_code = '$attrCode'");
            $attrId = $connection->fetchOne($select);

            $connection->update($catalogProductEntityInt, ['value' => 0], "attribute_id = '$attrId'");
        }
    }
}
