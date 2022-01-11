<?php
namespace CappasityTech\Magento3D\Model\ResourceModel\ImageParams;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package CappasityTech\Magento3D\Model\ResourceModel\ImageParams
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(
            'CappasityTech\Magento3D\Model\ImageParams',
            'CappasityTech\Magento3D\Model\ResourceModel\ImageParams'
        );
    }
}
