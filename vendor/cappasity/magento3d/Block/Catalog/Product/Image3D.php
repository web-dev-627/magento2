<?php

namespace CappasityTech\Magento3D\Block\Catalog\Product;

class Image3D extends \Magento\Catalog\Block\Product\View
{
    public function get3dImagesDataJson()
    {
        return $this->_jsonEncoder->encode($this->getProduct()->getData('image3d'));
    }
}
