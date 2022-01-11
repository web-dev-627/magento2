<?php

namespace CappasityTech\Magento3D\Block\Adminhtml\Button;

class SaveSynchronize extends \Magento\Backend\Block\Template
{
    protected $_template = '/button/save_synchronize.phtml';
    private $urlBuilder;
    private $syncSettingFactory;
    private $user;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \CappasityTech\Magento3D\Model\SettingFactory $settingFactory,
        array $data = []
    ) {
        $this->syncSettingFactory = $settingFactory;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    public function getSendDataUrl()
    {
        return $this->urlBuilder->getUrl('cappasity/index/syncjobparamssave');
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
}
