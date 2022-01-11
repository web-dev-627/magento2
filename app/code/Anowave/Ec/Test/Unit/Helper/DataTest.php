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

namespace Anowave\Ec\Test\Unit\Helper;

class DataTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Helper 
	 * 
	 * @var \Anowave\Ec\Helper\Data
	 */
	protected $helper;
	
	/**
	 * @var \Magento\Framework\App\Config\ScopeConfigInterface | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $configMock;
	
	/**
	 * Setup 
	 * 
	 * {@inheritDoc}
	 * @see \PHPUnit\Framework\TestCase::setUp()
	 */
	protected function setUp()
	{
		$object = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
		
		/**
		 * Create helper mockup 
		 * 
		 * @var \Anowave\Ec\Test\Unit\Helper\DataTest $helper
		 */
		$this->helper = $this->createMock(\Anowave\Ec\Helper\Data::class);
	}
	
	/**
	 * Test list selector 
	 * 
	 * @return boolean
	 */
	public function testGetListSelector()
	{
		return $this->assertNull($this->helper->getListSelector());
	}
}