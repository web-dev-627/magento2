<?php

namespace CappasityTech\Magento3D\Block\Adminhtml\Catalog\Product\Edit\Tab;

class Image3d extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'product/edit/image3d.phtml';

    private $urlBuilder;
    private $coreRegistry = null;
    private $syncData;

    public function __construct(
        \CappasityTech\Magento3D\Model\SyncDataFactory $syncData,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->syncData = $syncData;
        $this->urlBuilder = $urlBuilder;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->coreRegistry->registry('current_product');
    }

    public function getFromPicker()
    {
        return ($this->getProduct()->getFromPicker()) ? 1 : 0;
    }

    public function getImages()
    {
        $images = $this->syncData->create()->getCollection();
        $images->addFieldTofilter('orig_image_3d', ['neq' => 'NULL'])
            ->getSelect()->joinLeft(
                ['cpe' => $images->getTable('catalog_product_entity')],
                'main_table.product_id = cpe.entity_id',
                ['sku' => 'cpe.sku']
            )
            ->group('orig_image_3d');

        $result = [];
        foreach ($images as $image) {
            $tmp = $image->getData();
            $tmp['medium_thumbnail_3d'] = $image->getOrigMediumThumbnailUrl();
            $tmp['thumbnail_3d'] = $image->getOrigThumbnailUrl();
            $result[] = $tmp;
        }
        return $result;
    }

    public function getSaveUrl()
    {
        return $this->urlBuilder->getUrl('cappasity/product/saveimage');
    }

    public function getDeleteUrl()
    {
        return $this->urlBuilder->getUrl('cappasity/product/deleteimage');
    }

    public function getPaginationUrl()
    {
        return $this->urlBuilder->getUrl('cappasity/product/pagination');
    }
}
