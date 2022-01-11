<?php

namespace CappasityTech\Magento3D\Model\ResourceModel;

/**
 * Class CappasityTechParamsRule
 * @package CappasityTech\Magento3D\Model\ResourceModel
 */
class ParamsRule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(
            'cappasity_tech_magento3D_params_rule',
            'entity_id'
        );
    }
}
