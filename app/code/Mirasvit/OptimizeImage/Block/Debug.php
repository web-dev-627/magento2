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




namespace Mirasvit\OptimizeImage\Block;


use Magento\Framework\View\Element\Template;
use Mirasvit\OptimizeImage\Model\Config;

class Debug extends Template
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config, Template\Context $context, array $data = [])
    {
        $this->config = $config;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        if ($this->config->isDebug()) {
            $this->pageConfig->addPageAsset('Mirasvit_OptimizeImage::css/debug.css');
        }
    }
}
