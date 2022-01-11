<?php

namespace CappasityTech\Magento3D\Model\ResourceModel;

/**
 * Class CappasityTechPaymentPlan
 * @package CappasityTech\Magento3D\Model\ResourceModel
 */
class PaymentPlan extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init('cappasity_tech_magento3D_payment_plan', 'entity_id');
    }
}
