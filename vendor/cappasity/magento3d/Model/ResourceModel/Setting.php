<?php

namespace CappasityTech\Magento3D\Model\ResourceModel;

/**
 * Class CappasitySetting
 * @package Cappasity\Magento3D\Model\ResoucreModel
 */
class Setting extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Block\Switcher $switcher,
        \Psr\Log\LoggerInterface $logger,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->_switcher = $switcher;
        $this->logger = $logger;
    }

    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init('cappasity_tech_magento3D_user', 'entity_id');
    }
}
