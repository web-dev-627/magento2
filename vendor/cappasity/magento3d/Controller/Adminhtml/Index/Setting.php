<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class Setting extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;
    /**
     * @var \CappasityTech\Magento3D\Model\CappasitySettingFactory
     */
    private $settingFactory;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * Setting constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \CappasityTech\Magento3D\Model\CappasitySettingFactory $settingFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \CappasityTech\Magento3D\Model\SettingFactory $settingFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->settingFactory = $settingFactory;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|void
     */
    public function execute()
    {
        try {
            $resultPage = $this->resultPageFactory->create();

            if ($user = $this->settingFactory->create()->loadActiveUser()) {
                $user->loadActiveUser()->isTokenValid();
            }

            $title = __('Cappasity 3D and 360 Product Viewer');
            $resultPage->getConfig()->getTitle()->prepend($title);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CappasityTech_Magento3D::edit_cappasity_tech');
    }
}
