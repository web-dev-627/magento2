<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-optimize
 * @version   1.3.14
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\OptimizeJs\Block;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\Template;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\OptimizeJs\Api\Repository\BundleFileRepositoryInterface;

class Js extends Template
{
    const MODE_PARAM      = 'optimize_js';
    const MODE_BACKGROUND = 'background';
    const MODE_PRE_FLY    = 'pre-fly';

    private $urlBuilder;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    private $bundleFileRepository;

    public function __construct(
        BundleFileRepositoryInterface $bundleFileRepository,
        Context $context
    ) {
        $this->urlBuilder           = $context->getUrlBuilder();
        $this->request              = $context->getRequest();
        $this->bundleFileRepository = $bundleFileRepository;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     * @return string|void
     */
    public function _toHtml()
    {
        if (!$this->shouldAddBlock()) {
            return;
        }

        $baseUrl = $this->urlBuilder->getUrl('optimizeJs/bundle/track');

        $initObject = [
            'Mirasvit_OptimizeJs/js/bundle/track' => [
                'callbackUrl' => $baseUrl,
                'layout'      => $this->request->getFullActionName(),
                'mode'        => $this->request->getParam(self::MODE_PARAM, self::MODE_BACKGROUND),
            ],
        ];

        return '<div data-mage-init=\'' . SerializeService::encode($initObject) . '\'></div>';
    }

    /**
     * @return bool
     */
    private function shouldAddBlock()
    {
        $pageTypesForCheck = [
            'cms_index_index',
            'cms_page_view',
            'catalog_category_view',
            'catalog_product_view',
            'customer_account_index',
            'customer_account_login',
        ];

        $pageType = $this->request->getFullActionName();

        if (strpos($pageType, 'checkout') !== false) {
            return false;
        }

        $currentPageTypeFilesCount = $this->bundleFileRepository
            ->getCollection()
            ->addFieldToFilter('layout', $pageType)
            ->addFieldToFilter('area', 'frontend')
            ->getSize();

        $pageTypesForCheck = array_diff($pageTypesForCheck, [$pageType]);

        $otherPageTypesFilesCount = $this->bundleFileRepository
            ->getCollection()
            ->addFieldToFilter('layout', ['in' => $pageTypesForCheck])
            ->addFieldToFilter('area', 'frontend')
            ->getSize();


        if ($currentPageTypeFilesCount && $currentPageTypeFilesCount <= $otherPageTypesFilesCount / 3) {
            return false;
        }

        return true;
    }
}
