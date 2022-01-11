<?php

namespace CappasityTech\Magento3D\Model\ResourceModel;

class SyncJob extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init('cappasity_tech_magento3D_sync_job', 'entity_id');
    }
}
