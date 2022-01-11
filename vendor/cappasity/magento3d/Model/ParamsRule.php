<?php

namespace CappasityTech\Magento3D\Model;

class ParamsRule extends \Magento\Framework\Model\AbstractModel implements ParamsRuleInterface
{
    protected function _construct()
    {
        $this->_init(\CappasityTech\Magento3D\Model\ResourceModel\ParamsRule::class);
    }
}
