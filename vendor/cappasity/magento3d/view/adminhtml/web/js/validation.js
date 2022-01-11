require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate'
    ], function (validator, $) {

        validator.addRule(
            'validate-digits-10to120',
            function (v) {
                return ($.mage.isEmptyNoTrim(v) || !/[^\d]/.test(v)) && (v >= 10 && v <= 120) ;
                //return true or false based on your logic
            }
            , $.mage.__('Wrong time settings (from 10 to 120).')
        );
        validator.addRule(
            'validate-digits-2to30',
            function (v) {
                return ($.mage.isEmptyNoTrim(v) || !/[^\d]/.test(v)) && (v >= 2 && v <= 30) ;
                //return true or false based on your logic
            }
            , $.mage.__('Wrong time settings (from 2 to 30).')
        );
    });