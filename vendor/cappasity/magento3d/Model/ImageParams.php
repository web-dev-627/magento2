<?php

namespace CappasityTech\Magento3D\Model;

class ImageParams extends \Magento\Framework\Model\AbstractModel implements ImageParamsInterface
{
    protected function _construct()
    {
        $this->_init(\CappasityTech\Magento3D\Model\ResourceModel\ImageParams::class);
    }
}
