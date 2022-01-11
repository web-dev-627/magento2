<?php

namespace CappasityTech\Magento3D\Model;

interface SyncJobParamsInterface
{
    const ENTITY_ID = 'entity_id';
    const DATA_AUTO_SYNC_NEW_PRODUCT = 'auto_sync_new_product';
    const DATA_DONT_SYNC_MANUAL_CHOICES = 'dont_sync_manual';
    const DATA_USE_THUMBNAIL_OF_BUTTON = 'use_thumbnail_of_button';
    const DATA_ADD_PREVIEW_TO_GALLERY = 'add_preview_to_gallery';
    const DATA_SET_PREVIEW_BASE = 'set_preview_base';
    const DATA_USER_ID = 'user_id';
}
