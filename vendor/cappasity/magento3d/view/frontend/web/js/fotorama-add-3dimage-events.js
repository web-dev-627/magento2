define([
    'jquery',
    'jquery/ui'
], function ($) {
    "use strict";

    $.widget('mage.AddFotorama3dImageEvents', {
        options: {
            data: {},
            selectedOption: 'default'
        },

        _create: function () {
            $(this.element).on('gallery:loaded', $.proxy(function () {
                this._initialize();
            }, this));
        },

        _initialize: function () {
            this.fotoramaItem = $(this.element).find('.fotorama-item');
            this._addimages();
        },

        _addimages: function () {
            var $widget = this;
            var options = $widget.options;
            var fotorama = $widget.fotoramaItem.data('fotorama');

            $widget.fotoramaItem.on('fotorama:load', function fotorama_onLoad(e, fotorama, extra) {
                if (extra.frame.type === 'iframe') {
                    extra.frame.$stageFrame.html(extra.frame.html);
                    if (extra.frame.product == 'default' || options.selectedOption == extra.frame.product) {
                        extra.frame.$navThumbFrame.show()
                    } else {
                        extra.frame.$navThumbFrame.addClass('hidden');
                    }
                }
            });

            var default3dImage = options.data.default;
            if (default3dImage.image3durl) {
                fotorama.data[fotorama.data.length] = $widget._getIframeData('default', default3dImage);
            }
            var children = options.data.children;
            if (children) {
                $.each(children, function (product, child) {
                    if (child.image3durl) {
                        fotorama.data[fotorama.data.length] = $widget._getIframeData(product, child);
                    }
                })
            }
        },
        _getIframeData: function (product, data) {
            return {
                img: '',
                // isMain: true,
                product: product,
                thumb: data.thumbnail,
                src: data.image3durl,
                html: data.image3diframe,
                type: 'iframe'
            };
        },
    });

    return $.mage.AddFotorama3dImageEvents;
});