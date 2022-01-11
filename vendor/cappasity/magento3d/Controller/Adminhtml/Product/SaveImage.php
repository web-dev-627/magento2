<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Product;

use CappasityTech\Magento3D\Model\DataInterface;

class SaveImage extends \Magento\Backend\App\Action
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

            $productId = $params['id'];
            $product = $this->productRepository->getById($productId);
            $product->setCustomAttribute('from_picker', 1);
            $this->productRepository->save($product);

            $params['sku'] = $product->getSku();
            $params['capp'] = '';

            $this->syncDataFactory->create()->saveResultData([$params], 'custom_'.time());

            $responseData = ['status' => 'success', 'message' => __('Data was saved successfully')];
        } catch (\Exception $e) {
            $responseData = ['status' => 'error', 'message' => __('Error: ') . $e->getMessage()];
        }
        return $resultJson->setData($responseData);
    }
}
