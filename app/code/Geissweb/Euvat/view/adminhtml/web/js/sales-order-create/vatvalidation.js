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

//Remixed and simplyfied window.AdminOrder.prototype.validateVat with jQuery, for the time until it gets refactored

define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'mageUtils',
    'Geissweb_Euvat/js/model/syntax-validation'
], function (
    $,
    alertWidget,
    confirmWidget,
    $t,
    Utils,
    SyntaxValidator
) {
    'use strict';

    return function () {

        return window.AdminOrder.prototype.validateVat = function (parameters, button)
        {
            var countrySelect = $('#'+parameters.countryElementId);
            var countryCode = countrySelect.val();
            SyntaxValidator.setCountryCode(countryCode);
            var vatField = $('#'+parameters.vatElementId);

            var initialValue = vatField.val();
            if (initialValue.length <= 2) {
                alertWidget({
                    content: parameters.emptyValueMessage
                });
                return;
            }
            var vatNumber = vatField.val().replace(/[\W_]/g, "").toUpperCase().trim();

            if(!SyntaxValidator.hasCountryPrefix(vatNumber)) {
                vatNumber = SyntaxValidator.addCountryPrefix(vatNumber);
            }

            if (vatNumber !== initialValue) {
                vatField.val(vatNumber);
                var nativeField = document.getElementById(parameters.vatElementId);
                var evt = document.createEvent("HTMLEvents");
                evt.initEvent("change", false, true);
                nativeField.dispatchEvent(evt);
            }
            var groupSelect = $('#'+parameters.groupIdHtmlId);
            var currentCustomerGroupId = (groupSelect.length > 0) ? parseInt(groupSelect.val()) : 0;


            var params = {
                country: countryCode,
                vat: vatField.val(),
                form_key: window.FORM_KEY
            };
            if (window.order.storeId !== false) {
                params.store_id = window.order.storeId;
            }

            $.ajax({
                type: 'POST',
                url: parameters.validateUrl,
                data: params,
                showLoader: true,
                success: function (response) {
                    var message = '';
                    var groupActionRequired = 'inform';
                    var traderName = '';
                    var traderAddress = '';

                    try {
                        if (response.valid) {
                            message = parameters.vatValidAndGroupValidMessage;
                            if (currentCustomerGroupId !== response.group) {
                                message = parameters.vatValidAndGroupChangeMessage;
                                groupActionRequired = 'change';
                            }
                            if (!Utils.isEmpty(response.trader_name)) {
                                traderName = parameters.traderName.replace(/%s/, response.trader_name);
                            }
                            if (!Utils.isEmpty(response.trader_address)) {
                                traderAddress = parameters.traderAddress.replace(/%s/, response.trader_address);
                            }
                        } else if (response.success || !response.valid) {
                            message = parameters.vatInvalidMessage.replace(/%s/, params.vat);
                            groupActionRequired = 'inform';
                        } else {
                            message = parameters.vatValidationFailedMessage;
                            groupActionRequired = 'inform';
                        }

                    } catch (e) {
                        console.log(e);
                        message = parameters.vatValidationFailedMessage;
                        groupActionRequired = 'change';
                    }

                    var groupMessage = '';
                    try {
                        var currentCustomerGroupTitle = $('#'+parameters.groupIdHtmlId+' option[value="'+currentCustomerGroupId+'"]').text();
                        var targetGroupOptionTitle = $('#'+parameters.groupIdHtmlId+' option[value="'+response.group+'"]').text();
                        groupMessage = parameters.vatCustomerGroupMessage.replace(/%s/, targetGroupOptionTitle);
                    } catch (e) {
                        groupMessage = parameters.vatGroupErrorMessage;
                    }

                    if (groupActionRequired === 'change') {
                        var confirmText = message.replace(/%s/, targetGroupOptionTitle);
                        confirmText = confirmText.replace(/%s/, currentCustomerGroupTitle);
                        confirmWidget({
                            title: $t('Customer Group Change'),
                            content: confirmText + "<br/><br/>" + traderName + "<br/>" + traderAddress,
                            actions: {
                                confirm: function () {
                                    $('#'+parameters.groupIdHtmlId+' option[value="'+response.group+'"]')
                                        .prop('selected', true)
                                        .change();
                                    //window.order.accountGroupChange();
                                },
                                cancel: function () {},
                                always: function () {}
                            }
                        });

                    } else if (groupActionRequired === 'inform') {
                        alertWidget({
                            title: message,
                            content: groupMessage + "<br/><br/>" + traderName + "<br/>" + traderAddress
                        });
                    }
                },

                beforeSend: function () {
                    button.hide();
                },
                error: function (e) {
                    console.log("Something went wrong!");
                    console.log(e);
                },
                complete: function (response) {
                    button.show();
                }

                .bind(this)
            });//endAjax

        }
    }

});
