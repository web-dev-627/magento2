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



namespace Mirasvit\OptimizeJs\Plugin\Framework\View\Asset\Minification;

use Mirasvit\OptimizeJs\Model\Config;
use Magento\Framework\View\Asset\Minification;

/**
 * @see \Magento\Framework\View\Asset\Minification
 */
class JsMinificationPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $contentType = '';

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param Minification $subject
     * @param string       $contentType
     *
     * @return array
     */
    public function beforeIsEnabled(Minification $subject, $contentType)
    {
        $this->contentType = $contentType;

        return [$contentType];
    }

    /**
     * @param Minification $subject
     * @param bool         $result
     *
     * @return bool
     */
    public function afterIsEnabled(Minification $subject, $result)
    {
        return $this->contentType == 'js' ? $this->config->isMinifyJs() : $result;
    }

    /**
     * @param Minification $subject
     * @param string[]     $excludes
     * @param string       $contentType
     *
     * @return string[]
     */
    public function afterGetExcludes(Minification $subject, array $excludes, $contentType)
    {
        if ($contentType !== 'js') {
            return $excludes;
        }

        $exceptions = $this->config->getMinifyJsExceptions();

        $excludes = array_merge($excludes, $exceptions);
        return $excludes;
    }
}
