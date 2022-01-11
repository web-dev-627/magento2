var config = {
    config: {
        map: {
            '*': {
                fotorama3dImageEvents: 'CappasityTech_Magento3D/js/fotorama-add-3dimage-events',
                // SwatchRenderer: 'CappasityTech_Magento3D/js/swatch-renderer'
            }
        },
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'CappasityTech_Magento3D/js/swatch-renderer-mixin': true
            },
        }
    }
};
