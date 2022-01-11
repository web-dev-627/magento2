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



namespace Mirasvit\OptimizeImage\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement as AbstractElementAlias;

class CleanupButton extends Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Mirasvit_OptimizeImage::system/config/generate.phtml');

        return $this;
    }

    public function render(AbstractElementAlias $element)
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElementAlias $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id'      => $element->getHtmlId(),
                'callback_url' => $this->getUrl('optimizeImage/action/cleanup'),
            ]
        );

        return $this->_toHtml();
    }
}
