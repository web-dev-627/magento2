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
    'Geissweb_Euvat/js/form/element/vat-number-base',
    'Geissweb_Euvat/js/queue',
    'Magento_Ui/js/lib/validation/validator',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/quote',
    'Geissweb_Euvat/js/model/reload',
    'uiRegistry',
    'mageUtils'
], function ($,
             VatNumberBase,
             queue,
             validator,
             ShippingRatesValidator,
             Quote,
             Reloader,
             registry,
             Utils
) {
    'use strict';

    return VatNumberBase.extend({

        defaults: {
            isBillingField: false
        },

        initialize: function (options) {
            this.element = this._super();
            this.initObservable().setCssClasses();
            var self = this;
            this.isBillingField = RegExp('billing', 'i').test(self.parentScope);

            for (var property in self.validation) {
                if (self.validation.hasOwnProperty(property)
                    && property === 'valid-vat-required'
                ) {
                    _.extend(self.additionalClasses, {
                        _required: true
                    });

                //Required for Amasty checkout which adds required-entry:false
                } else if (self.validation.hasOwnProperty(property)
                    && property === 'required-entry'
                ) {
                    var isRequired = self.validation['required-entry'];
                    if(!isRequired) {
                        delete self.validation['required-entry'];
                    }
                    _.extend(self.additionalClasses, {
                        _required: isRequired
                    });
                }
            }

            if (this.parentScope === 'shippingAddress') {
                if (options.delay === 'undefined') {
                    options.delay = 0;
                }
                ShippingRatesValidator.bindHandler(self.element, options.delay + 500);
            }

            registry.async('checkoutProvider')(function (checkoutProvider) {
                if (!Utils.isEmpty(self.parentScope) && self.isBillingField) {
                    checkoutProvider.on(self.parentScope, function (billingAddressData) {
                        self.setBillingVisibility(billingAddressData.country_id, self.parentScope);
                    });
                } else if (!Utils.isEmpty(self.parentScope) && self.parentScope === 'shippingAddress') {
                    checkoutProvider.on('shippingAddress', function (shippingAddressData) {
                        self.setShippingVisibility(shippingAddressData.country_id);
                    });
                }
            });

            if (this.debug) {
                console.log('vatNumberCo init '+self.uid, self);
            }

            return self;
        },

        /**
         * Sets field visibility based on country
         */
        setBillingVisibility: function (countryValue, parentScope) {
            if (this.debug) {
                console.log('setBillingVisibility', countryValue);
            }
            if (!Utils.isEmpty(this.parentScope) && this.parentScope === parentScope) {
                this.setVisibility(countryValue, 'billing');
            }
        },

        /**
         * Sets field visibility based on country
         */
        setShippingVisibility: function (countryValue) {
            if (this.debug) {
                console.log('setShippingVisibility', countryValue);
            }
            if (!Utils.isEmpty(this.customScope) && this.customScope === 'shippingAddress') {
                this.setVisibility(countryValue, 'shipping');
            }
        },

        /**
         * Sets the visibility of the field
         * @param countryValue
         * @param addressType
         */
        setVisibility: function (countryValue, addressType) {
            if (this.debug) {
                console.log("setVisibility", [
                    this.vatFrontendVisibility,
                    countryValue,
                    addressType,
                    this.fieldVisibleCountries,
                    $.isArray(this.fieldVisibleCountries),
                    $.inArray(countryValue, this.fieldVisibleCountries) !== -1
                ]);
            }

            if (this.vatFrontendVisibility === true
                && $.isArray(this.fieldVisibleCountries)
                && $.inArray(countryValue, this.fieldVisibleCountries) !== -1
            ) {
                this.visible(true);

            } else {
                this.value('');
                this.visible(false);
            }
        },

        /**
         * Sets field css classes
         */
        setCssClasses: function () {
            if (this.classes === null) {
                var classes = ['input-text'];
                for (var property in this.validation) {
                    if (this.validation.hasOwnProperty(property)) {
                        classes.push(property);
                    }
                }
                this.classes = "";
                classes.forEach(function (name) {
                    this.classes += " "+name;
                }, this);
            }
            this.classes = this.classes.trim();
        },

        afterValidation: function (jqXHR) {
            var self = this;
            queue.addFunction(function () {
                var deferred = new $.Deferred();
                if (self.countryCode.length > 0 ) {
                    $('body').trigger('processStop');
                    return self.updateCountry(self.countryCode, deferred);
                }
            });

            queue.addFunction(function () {
                if (self.debug) {
                    console.log("Reloader.reloadShippingMethodsAmasty()");
                }
                return Reloader.reloadShippingMethodsAmasty();
            });

            return queue.run();
        }

    });
});

