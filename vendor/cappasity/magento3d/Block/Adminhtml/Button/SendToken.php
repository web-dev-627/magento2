<?php

namespace CappasityTech\Magento3D\Block\Adminhtml\Button;

class SendToken extends \Magento\Backend\Block\Template
{
    protected $_template = '/button/send_token.phtml';
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
        return $this->urlBuilder->getUrl('cappasity/index/settingsave');
    }
}
