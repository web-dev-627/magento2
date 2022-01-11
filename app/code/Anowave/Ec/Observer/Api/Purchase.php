<?php
/**
 * Anowave Magento 2 Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2021 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

namespace Anowave\Ec\Observer\Api;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * 
 * @uses
 * 
 * Fires on event: 
 * 
 * ec_api_measurement_protocol_purchase 
 * 
 * @example using \Magento\Framework\Event\ManagerInterface $eventManager
 * 
 * $this->_eventManager->dispatch('ec_api_measurement_protocol_purchase', ['order_id' => $order_id]);
 */

class Purchase implements ObserverInterface
{
	/**
	 * @var \Anowave\Ec\Model\Api\Measurement\Protocol
	 */
	protected $protocol;
	
	/**
	 * Constructor 
	 * 
	 * @param \Anowave\Ec\Model\Api\Measurement\Protocol $protocol
	 */
	public function __construct
	(
		\Anowave\Ec\Model\Api\Measurement\Protocol $protocol
	)
	{
		$this->protocol = $protocol;	
	}
	
	/**
	 * Execute (non-PHPdoc)
	 * 
	 * @see \Magento\Framework\Event\ObserverInterface::execute()
	 */
	public function execute(EventObserver $observer)
	{
		if ($observer->getOrderId())
		{
			$this->protocol->purchaseById
			(
				$observer->getOrderId()
			);
		}
	}
}