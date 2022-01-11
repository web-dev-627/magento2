<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Product;

use CappasityTech\Magento3D\Model\DataInterface;

class DeleteImage extends \Magento\Backend\App\Action
{
    private $productRepository;
    private $syncDataFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \CappasityTech\Magento3D\Model\SyncDataFactory $syncDataFactory
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->syncDataFactory = $syncDataFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        try {
            $params = $this->getRequest()->getParams();
            /*Remove Images From Product*/
            $productId = $params['id'];
            $imageId = $params['image3dId'];
            $image3d = $this->syncDataFactory->create()->getProductImage3D($productId);
            if (!$image3d->getId()) {
                throw  new \Exception(__("3d Image is not found."));
            }
            $image3d->delete();
            $product = $this->productRepository->getById($productId);
            $existingMediaGalleryEntries = $product->getMediaGalleryEntries();

            foreach ($existingMediaGalleryEntries as $key => $entry) {
                if (strpos($entry->getFile(), $imageId) !== false) {
                    unset($existingMediaGalleryEntries[$key]);
                }
            }
            $product->setMediaGalleryEntries($existingMediaGalleryEntries);
            $this->productRepository->save($product);

            $responseData = ['status' => 'success', 'message' => __('Image was deleted successfully.')];
        } catch (\Exception $e) {
            $responseData = ['status' => 'error', 'message' => __('Error: ') . $e->getMessage()];
        }
        return $resultJson->setData($responseData);
    }
}
