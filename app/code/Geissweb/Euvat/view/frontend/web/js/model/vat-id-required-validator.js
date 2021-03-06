/**
 * ||GEISSWEB| EU VAT Enhanced
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GEISSWEB End User License Agreement
 * that is available through the world-wide-web at this URL: https://www.geissweb.de/legal-information/eula
 *
 * DISCLAIMER
 *
 * Do not edit this file if you wish to update the extension in the future. If you wish to customize the extension
 * for your needs please refer to our support for more information.
 *
 * @copyright   Copyright (c) 2015 GEISS Weblösungen (https://www.geissweb.de)
 * @license     https://www.geissweb.de/legal-information/eula GEISSWEB End User License Agreement
 */

define([
    'jquery',
    'mage/translate',
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'mageUtils',
    'uiRegistry'
],function (
    $,
    $t,
    url,
    messageList,
    CustomerModel,
    Quote,
    Utils,
    Registry
) {
    'use strict';

    return {
        validate: function (hideError) {
            var isValid = true;
            var address = null, vatField = null;

            if(CustomerModel.isLoggedIn())
            {
                if(Quote.isVirtual()) {
                    address = Quote.billingAddress();
                    vatField = Registry.get("checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.vat_id");
                } else {
                    address = Quote.shippingAddress();
                    vatField = Registry.get("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.vat_id");
                }

                if(vatField.debug) {
                    console.log('vat-number-required payment validator', vatField);
                }

                if(Utils.isEmpty(address.vatId)) {
                    if( (!Utils.isEmpty(address.countryId) && !Utils.isEmpty(vatField.euCountries))
                        && $.inArray(address.countryId, vatField.euCountries) === -1
                    ) {
                        isValid = true;
                    } else {
                        isValid = false;
                        messageList.addErrorMessage({
                            message: $t('A valid VAT number is required for checkout. Please edit your address to include your VAT number.')
                        });
                    }
                    return isValid;
                }

                isValid = false;
                $.when( $.ajax({
                    type: 'POST',
                    async: false,
                    url: url.build('euvat/vatnumber/validation/'),
                    data: {
                        vat_number: address.vatId,
                        form_key: $.cookie('form_key'),
                        handle: 'checkout_additional_validator'
                    }
                }) ).done(function( data, textStatus, jqXHR ) {
                    isValid = data.vat_is_valid;
                    if(data.vat_is_valid === false) {
                        messageList.addErrorMessage({
                            message: $t('A valid VAT number is required for checkout. Please edit your address to include your VAT number.')
                        });
                        isValid = false;
                    } else if(data.vat_is_valid === true) {
                        isValid = true;
                    }
                });

            }

            return isValid;
        }
    }
});
