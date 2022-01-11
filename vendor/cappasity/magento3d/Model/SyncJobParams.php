<?php

namespace CappasityTech\Magento3D\Model;

class SyncJobParams extends \Magento\Framework\Model\AbstractModel implements SyncJobParamsInterface
{
    protected function _construct()
    {
        $this->_init(\CappasityTech\Magento3D\Model\ResourceModel\SyncJobParams::class);
    }

    public function loadByUserId($userId)
    {
        return $this->load($userId, SyncJobParamsInterface::DATA_USER_ID);
    }

    public function isAutoSyncProduct()
    {
        return boolval($this->getAutoSyncNewProduct());
    }

    public function isDontSyncManual()
    {
        return boolval($this->getDontSyncManual());
    }
}
