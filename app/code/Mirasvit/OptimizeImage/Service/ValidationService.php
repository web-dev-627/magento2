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



namespace Mirasvit\OptimizeImage\Service;

use Magento\Framework\Shell;
use Mirasvit\Core\Service\AbstractValidator;
use Mirasvit\OptimizeImage\Model\Config;

class ValidationService extends AbstractValidator
{
    private $config;
    
    private $shell;

    public function __construct(Config $config, Shell $shell)
    {
        $this->config = $config;
        $this->shell  = $shell;
    }

    public function testImageOptimizationAbility()
    {
        $extensions   = ['jpg', 'png', 'gif'];
        $canOptimize  = array_filter($extensions, [$this, 'canRunOptimizationFor']);
        $cantOptimize = array_diff($extensions, $canOptimize);

        try {
            $this->shell->execute("pwd");
        } catch(\Exception $e) {
            $this->addWarning(
                'I can\'t check because of: ' . $e->getMessage() 
                . '<br/> Please check in the terminal using the command <b>' 
                . '<code>bin/magento mirasvit:optimize-image:validate</code></b>'
            );
            return;
        }

        if(count($cantOptimize)) {
            $this->addError(
                'Can\'t run optimization for '
                . strtoupper(implode(', ', $cantOptimize))
                . ' images.<br/>Run <b><code>bin/magento mirasvit:optimize-image:validate</code></b> for more details.'
            );
        }

        if(!$this->canConvertWebp()) {
            $this->addError(
                'Can\'t convert images to the WEBP format.'
                . '.<br/>Run <b><code>bin/magento mirasvit:optimize-image:validate</code></b> for more details.'
            );
        }
    }

    /**
     * @return bool
     */
    public function canConvertWebp()
    {
        return $this->canRun('cwebp -h');
    }

    /**
     * @param string $extension
     * @return bool
     */
    public function canRunOptimizationFor($extension)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return $this->canRun('jpegoptim --version');
            case 'png':
                return  $this->canRun('optipng --version');
            case 'gif':
                return $this->canRun('gifsicle --version');
            default:
                return false;
        }
    }

    /**
     * @param string $cmd
     * @return bool
     */
    private function canRun($cmd)
    {
        try {
            $this->shell->execute($cmd);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
