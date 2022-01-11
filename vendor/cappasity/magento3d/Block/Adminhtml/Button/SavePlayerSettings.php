<?php

namespace CappasityTech\Magento3D\Block\Adminhtml\Button;

class SavePlayerSettings extends \Magento\Backend\Block\Template
{
    protected $_template = '/button/save_playersettings.phtml';
    private $urlBuilder;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    public function getSendDataUrl()
    {
        return $this->urlBuilder->getUrl('cappasity/index/saveplayersettings');
    }
}
