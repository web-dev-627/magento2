<?php

namespace CappasityTech\Magento3D\Controller\Adminhtml\Index;

class Action extends \Magento\Backend\App\Action
{
    private $urlBuilder;
    private $coreRegistry;
    private $settingCollectionFactory;
    private $settingFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\UrlInterface $urlBuilder,
        \CappasityTech\Magento3D\Model\SettingFactory $settingFactory,
        \CappasityTech\Magento3D\Model\ResourceModel\Setting\CollectionFactory $settingCollectionFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->settingCollectionFactory = $settingCollectionFactory;
        $this->settingFactory = $settingFactory;
        $this->urlBuilder = $urlBuilder;
    }

    public function execute()
    {
        $currentUserId = $this->coreRegistry->registry('CappasityTechUserId');

        if (!$currentUserId) {
            $settingModel = $this->settingFactory->create();
            $currentUser = $settingModel->loadActiveUser();
            if ($currentUser) {
                $currentUserId = $currentUser->getEntityId();
                $this->coreRegistry->register('CappasityTechUserId', $currentUserId);
            }
        }

        if ($currentUserId) {
            $url = $this->urlBuilder->getUrl(
                'cappasity/index/setting',
                ['entity_id' => $currentUserId]
            );
        } else {
            $url = $this->urlBuilder->getUrl('cappasity/index/setting');
        }

        $this->_response->setRedirect($url);
    }
}
