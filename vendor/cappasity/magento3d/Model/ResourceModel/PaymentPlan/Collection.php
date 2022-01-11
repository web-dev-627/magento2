<?php

namespace CappasityTech\Magento3D\Model\ResourceModel\PaymentPlan;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package CappasityTech\Magento3D\Model\ResourceModel\PaymentPlan
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'cappasity_plan_event';
    protected $_eventObject = 'cappasity_plan_object';

    protected function _construct()
    {
        $this->_init(
            'CappasityTech\Magento3D\Model\PaymentPlan',
            'CappasityTech\Magento3D\Model\ResourceModel\PaymentPlan'
        );
    }
}
