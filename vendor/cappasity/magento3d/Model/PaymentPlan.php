<?php

namespace CappasityTech\Magento3D\Model;

class PaymentPlan extends \Magento\Framework\Model\AbstractModel implements PaymentPlanInterface
{
    protected function _construct()
    {
        $this->_init(\CappasityTech\Magento3D\Model\ResourceModel\PaymentPlan::class);
    }

    public function getAllLabelPlan()
    {
        $result = [];
        $collection = $this->getCollection();
        foreach ($collection as $item) {
            $result[$item->getValue()] = $item->getLabel();
        }
        return $result;
    }

    public function getCodePlanByLabel($label)
    {
        $result = null;
        $collection = $this->getCollection();
        foreach ($collection as $item) {
            if ($item->getLabel() == $label) {
                $result = (int)$item->getValue();
                break;
            }
        }
        return $result;
    }
}
