/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            capPagination: 'CappasityTech_Magento3D/js/pagination.min',
        }
    },
    shim: {
        capPagination: {
            deps: ['jquery']
        },
    }
};
