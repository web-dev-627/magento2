<?php
namespace CappasityTech\Magento3D\Model\Config\Source;

class AutorotateDirection implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '-1', 'label' => __('Left')],
            ['value' => '1', 'label' => __('Right')],
        ];
    }
}
