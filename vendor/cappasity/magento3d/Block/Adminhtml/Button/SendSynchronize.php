<?php

namespace CappasityTech\Magento3D\Block\Adminhtml\Button;

class SendSynchronize extends \Magento\Backend\Block\Template
{
    protected $_template = '/button/send_synchronize.phtml';

    private $urlBuilder;
    private $syncSettingFactory;
    private $syncJobFactory;
    private $user=null;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \CappasityTech\Magento3D\Model\SyncJobFactory $syncJobFactory,
        \CappasityTech\Magento3D\Model\SettingFactory $settingFactory,
        array $data = []
    ) {
        $this->syncSettingFactory = $settingFactory;
        $this->syncJobFactory = $syncJobFactory;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    public function getActiveUser()
    {
        if (empty($this->user)) {
            $this->user = $this->syncSettingFactory->create()->loadActiveUser();
        }
        return $this->user;
    }

    public function getActiveToken()
    {
        return $this->getActiveUser()->getToken();
    }

    public function loadActiveSyncJob()
    {
        $activeJob = $this->syncJobFactory->create()->loadActiveSyncJob($this->getActiveUser());
        if (!$activeJob) {
            return false;
        }
        return true;
    }

    public function getSyncDataUrl()
    {
        return $this->urlBuilder->getUrl('cappasity/index/syncdata');
    }
}
