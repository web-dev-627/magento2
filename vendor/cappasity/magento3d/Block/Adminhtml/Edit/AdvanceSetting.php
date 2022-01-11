<?php

namespace CappasityTech\Magento3D\Block\Adminhtml\Edit;

class AdvanceSetting extends \Magento\Backend\Block\Template
{
    private $settingFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \CappasityTech\Magento3D\Model\SettingFactory $settingFactory,
        array $data = []
    ) {
        $this->settingFactory = $settingFactory;
        parent::__construct($context, $data);
    }

    public function loadActiveUser()
    {
        return $this->settingFactory->create()->loadActiveUser();
    }
}
