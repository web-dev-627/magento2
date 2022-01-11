<?php
namespace CappasityTech\Magento3D\Model\ResourceModel\Setting;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package CappasityTech\Magento3D\Model\ResourceModel\Setting
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'cappasity_setting_event';
    protected $_eventObject = 'cappasity_setting_object';
    
    protected function _construct()
    {
        $this->_init(
            'CappasityTech\Magento3D\Model\Setting',
            'CappasityTech\Magento3D\Model\ResourceModel\Setting'
        );
    }
}
