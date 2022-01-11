<?php
namespace CappasityTech\Magento3D\Model\Config\Source;

class ZoomQuality implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('SD')],
            ['value' => '2', 'label' => __('HD')],
        ];
    }
}
