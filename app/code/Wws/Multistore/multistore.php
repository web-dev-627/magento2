<?php

namespace Wws\Multistore;

use Exception;
use Wws\Multistore\Helper\Exception\StoreNotRegisteredException;
use Wws\Multistore\Helper\Loader;

if (php_sapi_name() !== 'cli') try {

    // Force magento to use the store code in $_SERVER by unsetting any store cookies.
    unset($_COOKIE['store']);

    $multistoreLoader = new Loader(BP . '/var/stores.json');
    $multistoreLoader->apply($_SERVER);

} catch (StoreNotRegisteredException $e) {
    // Store view is not registered, display default store.
} catch (Exception $e) {
    // Something else went wrong, log it and continue.
    error_log($e);
} finally {
    unset($multistoreLoader);
}
