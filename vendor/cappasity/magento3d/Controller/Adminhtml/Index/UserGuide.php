<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Index;

/**
 * Class UserGuide
 * @package CappasityTech\Magento3D\Controller\Adminhtml\Index
 */
class UserGuide extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'CappasityTech_Magento3D::userguide';
    /**
     * @var \CappasityTech\Magento3D\Helper\Data
     */
    private $cappasityHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \CappasityTech\Magento3D\Helper\Data $сappasityHelper
    ) {
        parent::__construct($context);
        $this->cappasityHelper = $сappasityHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $userGuideUrl = $this->cappasityHelper->getConfigValue('cappasitytech/general/userguide_url');
        $this->_response->setRedirect($userGuideUrl);
    }
}
