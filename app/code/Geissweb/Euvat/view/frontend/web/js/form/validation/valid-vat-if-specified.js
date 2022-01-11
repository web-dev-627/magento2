define([
    'jquery',
    'mage/translate',
    'mageUtils',
    'uiRegistry',
    './valid-vat-required'
], function(
    $,
    $t,
    Utils,
    uiRegistry,
    validVatRequiredValidation
) {
    'use strict';

    return function(value, component) {

        if(typeof component !== 'object') {
            return true;
        }

        if(component.debug) {
            console.log('valid-vat-if-specified validation', [
                component.visible(),
                typeof(component.value()),
                component.value().length
            ]);
        }

        if (component.visible() && typeof(component.value()) === 'string' && component.value().length > 0) {
            return validVatRequiredValidation(value, component);
        }

        return true;
    };

});


