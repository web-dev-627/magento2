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

namespace Anowave\Ec\Observer\Cookie;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Anowave\Ec\Observer\Cookie as Cookie;

class Search extends Cookie implements ObserverInterface
{
	/**
	 * Execute (non-PHPdoc)
	 *
	 * @see \Magento\Framework\Event\ObserverInterface::execute()
	 */
	public function execute(EventObserver $observer)
	{   
		if (null !== $query = $this->request->getParam('q'))
		{
			$private = [];
			
			if ($this->privateData->get())
			{
				/**
				 * Get private data 
				 * 
				 * @var array $privateData
				 */
				$private = (array) $this->jsonHelper->jsonDecode($this->privateData->get());		
			}
			
			/**
			 * Get search
			 */
			$private['search'] = $query;
			
			/**
			 * Update private data
			 */
			$this->privateData->set
			(
				$this->jsonHelper->jsonEncode($private)
			);
		}
	}
}