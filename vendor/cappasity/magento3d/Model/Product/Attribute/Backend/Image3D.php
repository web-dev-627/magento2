<?php

namespace CappasityTech\Magento3D\Model\Product\Attribute\Backend;

class Image3D extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    private $syncDataFactory;

    public function __construct(
        \CappasityTech\Magento3D\Model\SyncDataFactory $cappasityTechSyncData
    ) {
        $this->syncDataFactory = $cappasityTechSyncData;
    }

    public function afterLoad($object)
    {
        $value = ['default' => [], 'children' => [], 'image3did' => '', 'image3diframe' => ''];
        if ($image3d = $this->syncDataFactory->create()->getProductImage3D($object->getId())) {
            $value['default']['thumbnail'] = $image3d->getThumbnailUrl();
            $value['default']['image3durl'] = $image3d->getImage3dUrl();
            $value['default']['image3did'] = $image3d->getImage3d();
            $value['default']['image3diframe'] = $image3d->getImage3dIframe();
        }

        if ($object->getTypeId() == 'configurable') {
            $_children = $object->getTypeInstance()->getUsedProducts($object);
            foreach ($_children as $child) {
                if ($image3d = $this->syncDataFactory->create()->getProductImage3D($child->getId())) {
                    $value['children'][$child->getId()]['thumbnail'] = $image3d->getThumbnailUrl();
                    $value['children'][$child->getId()]['image3durl'] = $image3d->getImage3dUrl();
                    $value['children'][$child->getId()]['image3diframe'] = $image3d->getImage3dIframe();
                }
            }
        }

        $object->setData($this->getAttribute()->getAttributeCode(), $value);

        return parent::afterLoad($object);
    }
}
