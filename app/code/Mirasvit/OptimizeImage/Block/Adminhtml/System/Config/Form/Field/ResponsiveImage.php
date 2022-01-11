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



namespace Mirasvit\OptimizeImage\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class ResponsiveImage extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_addAfter = false;

        $this->addColumn('file', ['label' => __('File name')]);
        $this->addColumn('800', ['label' => __('Desktop width')]);
        $this->addColumn('480', ['label' => __('Mobile width')]);

        parent::_construct();
    }
}
