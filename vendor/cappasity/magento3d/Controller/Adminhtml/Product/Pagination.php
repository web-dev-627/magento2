<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Product;

use CappasityTech\Magento3D\Model\DataInterface;
use CappasitySDK\Client\Model\Request;
use CappasitySDK\Client\Model\Response;

class Pagination extends \Magento\Backend\App\Action
{
    private $productRepository;
    private $syncDataFactory;
    private $cappasityHelper;
    private $request;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \CappasityTech\Magento3D\Helper\Data $cappasityHelper,
        \CappasityTech\Magento3D\Model\SyncDataFactory $syncDataFactory
    )
    {
        parent::__construct($context);
        $this->request = $context->getRequest();
        $this->productRepository = $productRepository;
        $this->syncDataFactory = $syncDataFactory;
        $this->cappasityHelper = $cappasityHelper;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);

        try {
            $user = $this->cappasityHelper->getActiveUser();
            $token = $user->getToken();

            if (!$user || !$token) {
                throw new \CappasityTech\Magento3D\Model\Exceptions\ValidationTokenException(__('Please input a correct token.'));
            }

            $client = \CappasitySDK\ClientFactory::getClientInstance(['apiToken' => $token]);
            $limit = 10;

            $offset = ($this->request->getParam('page') - 1) * $limit;
            $filter = null;
            $searchFilter = $this->request->getParam('filter');
            if ($searchFilter) {
                $filter = ['alias' => [\CappasitySDK\Client\Model\Request\Files\ListGet::FILTER_MATCH => $searchFilter]];
            }
            $requestParams = \CappasitySDK\Client\Model\Request\Files\ListGet::fromData($limit, $offset, null, null, $filter);
            /** @var Response\Files\ListGet $response */

            $response = $client->getViewList($requestParams)->getBodyData();

            /** @var Response\Files\Common\File[] */

            $views = $response->getData();

            $images = [];
            foreach ($views as $view) {
                if (!$view->getAttributes()->getAlias()) {
                    continue;
                }
                $images[] = [
                    "sku" => $view->getAttributes()->getAlias(),
                    "id" => $view->getId(),
                    "link" => $this->cappasityHelper->generatePreviewImageSrc($view->getId(), 'medium')
                ];
            }
            $meta = $response->getMeta();
            $result = [
                "page" => $meta->getPage(),
                "pages" => $meta->getPages(),
                "data" => $images
            ];
            $responseData = ['status' => 'success', 'data' => $result];
        } catch (\Exception $ex) {
            $responseData = ['status' => 'error', 'message' => $ex->getMessage()];
        }

        return $resultJson->setData($responseData);
    }
}
