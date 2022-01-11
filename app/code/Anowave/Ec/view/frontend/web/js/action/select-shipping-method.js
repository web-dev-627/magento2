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

define(['jquery','mage/utils/wrapper', 'Magento_Checkout/js/model/quote'], function($, wrapper, quote)
{
    'use strict';

    return function(shippingMethod) 
    {
        return wrapper.wrap(shippingMethod, function (originalAction, method) 
        {
        	if ('undefined' !== typeof dataLayer && 'undefined' !== typeof data)	
        	{
        		(function(dataLayer, jQuery, shippingMethod)
        		{
        			var method = '';
        			
        			if (shippingMethod && shippingMethod.hasOwnProperty('method_title'))
        	    	{
        	    		method = shippingMethod.method_title;
        	    	}

        			if ('undefined' !== typeof AEC && 'undefined' !== typeof AEC.Const)
        			{
        				AEC.Checkout.stepOption(AEC.Const.CHECKOUT_STEP_SHIPPING, method);
        			}

        		})(dataLayer, jQuery, method);
        	}
        	
            return originalAction(method);
        });
    };
});