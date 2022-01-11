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

namespace Anowave\Ec\Model;

class Dom extends \DOMDocument
{
	/**
	 * Creates a new DOMDocument object
	 * 
	 * @param $version [optional]
	 * @param $encoding [optional]
	 */
	public function __construct($version = null, $encoding = null)
	{
		parent::__construct($version, $encoding);
	}
	
	/**
	 * Load HTML
	 * 
	 * {@inheritDoc}
	 * @see DOMDocument::loadHTML()
	 */
	public function loadHTML($source, $options = null)
	{
		if (function_exists('libxml_use_internal_errors'))
		{
			libxml_use_internal_errors(true);
		}

		if (empty($source))
		{
			return $source;
		}
		
		return parent::loadHTML($source, $options);
	}
}