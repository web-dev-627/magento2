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



namespace Mirasvit\OptimizeImage\Plugin;

use Mirasvit\OptimizeImage\Model\Config;

class FotoramaWebpSupportPlugin
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param mixed $subject
     * @param array $data
     * @return mixed
     */
    public function afterGetData($subject, $data)
    {
        if ($this->config->isWebpFotoramaEnabled()) {
            $data['mst_webp'] = (int)$this->config->isUseWebpForFotorama();
        }

        return $data;
    }
}
