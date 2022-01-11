define([
    'jquery',
    'jquery/ui',
    'Magento_Swatches/js/swatch-renderer'
], function ($) {
    $.widget('mage.SwatchRenderer', $.mage.SwatchRenderer, {
        // _init: function () {
        //     console.log('Magenmagic SwatchRenderer: _init');
        //
        //     if (this.options.jsonConfig !== '' && this.options.jsonSwatchConfig !== '') {
        //         this._sortAttributes();
        //         this._RenderControls();
        //     } else {
        //         console.log('SwatchRenderer: No input data received');
        //     }
        // },
        processUpdateBaseImage: function (images, context, isInProductView, gallery) {
            alert('processUpdateBaseImage');
        }
    });
    return $.mage.SwatchRenderer;
});