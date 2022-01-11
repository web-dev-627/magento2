<?php

namespace CappasityTech\Magento3D\Model\ResourceModel\ParamsRule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package CappasityTech\Magento3D\Model\ResourceModel\ParamsRule
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'cappasity_rule_event';
    protected $_eventObject = 'cappasity_rule_object';

    protected function _construct()
    {
        $this->_init(
            'CappasityTech\Magento3D\Model\ParamsRule',
            'CappasityTech\Magento3D\Model\ResourceModel\ParamsRule'
        );
    }
}
