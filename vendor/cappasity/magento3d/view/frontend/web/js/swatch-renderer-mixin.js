define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.SwatchRenderer', widget, {

            processUpdateBaseImage: function (images, context, isInProductView, gallery) {
                $(this.options.mediaGallerySelector).AddFotorama3dImageEvents({
                    selectedOption: this.getProduct(),
                    dataMergeStrategy: this.options.gallerySwitchStrategy
                });
                return this._super(images, context, isInProductView, gallery);
            },
        });
        return $.mage.SwatchRenderer;
    }
});