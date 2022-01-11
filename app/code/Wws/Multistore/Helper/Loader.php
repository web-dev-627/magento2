<?php

namespace Wws\Multistore\Helper;

use LogicException;
use Magento\Store\Model\StoreManager;
use Wws\Multistore\Helper\Exception\StoreNotRegisteredException;
use RuntimeException;

/**
 * Class Loader
 */
class Loader
{

    /** @var array */
    private $stores;

    /**
     * Loader constructor.
     *
     * @param $filename
     */
    public function __construct($filename)
    {
        $this->stores = $this->getStores($filename);
    }

    /**
     * Returns store configuration array from configuration json file.
     *
     * @param string $filename
     * @return array
     */
    private function getStores($filename)
    {
        if (!is_file($filename)) {
            throw new RuntimeException('Store configuration file doesn\'t exist.');
        }
        if (!($content = @file_get_contents($filename))) {
            throw new RuntimeException('Can\'t read store configuration file.');
        }
        if (!($stores = @json_decode($content, true)) || json_last_error()) {
            throw new RuntimeException('Can\'t decode store configuration data: ' . json_last_error_msg());
        }
        return $stores;
    }

    /**
     * Applies Magento store view configuration to the $server array based off request path $path.
     *
     * @param array $server
     * @throws StoreNotRegisteredException
     */
    public function apply(array & $server)
    {
        if ($code = $this->getStoreViewCode($server)) {
            $server[StoreManager::PARAM_RUN_CODE] = $code;
            $server[StoreManager::PARAM_RUN_TYPE] = StoreManager::CONTEXT_STORE;
        } else {
            throw new StoreNotRegisteredException(sprintf('No store registered for this path.'));
        }
    }

    /**
     * Attempts to parse the store view from the request url.
     *
     * @param array $server
     * @return string
     */
    private function getStoreViewCode(array $server)
    {
        if (!isset($server['HTTP_HOST'])) {
            throw new LogicException('HTTP_HOST not defined.');
        }
        if (!isset($server['REQUEST_URI'])) {
            throw new LogicException('REQUEST_URI not defined.');
        }

        // Determine scheme
        $scheme = isset($server['HTTPS']) ? 'https' : 'http';

        // Get the url including scheme but excluding query etc.
        $path = rtrim(sprintf('%s://%s%s',
            $scheme,
            $server['HTTP_HOST'],
            current(explode('?', $server['REQUEST_URI']))
        ), '/');

        $pathWithoutScheme = str_replace($scheme . '://', '', $path);

        // Strip off every trailing /path until we can match the url to a registered store view.
        do {
            if (array_key_exists($path . '/', $this->stores)) {
                return $this->stores[$path . '/'];
            }
            // Check if store has secured url
            $altPath = str_replace('http', 'https', $path);
            if (array_key_exists($altPath . '/', $this->stores)) {
                return $this->stores[$altPath . '/'];
            }
        } while ($path = substr($path, 0, strrpos($path, '/')));

        // Check if www. is in the path
        if (substr($pathWithoutScheme, 0, 4) == 'www.') {
            // remove www.
            $path = $scheme . '://' . substr($pathWithoutScheme, 4);
        } else {
            // add www.
            $path = $scheme . '://www.' . $pathWithoutScheme;
        }

        // Try again with modified path
        // Strip off every trailing /path until we can match the url to a registered store view.
        do {
            if (array_key_exists($path . '/', $this->stores)) {
                return $this->stores[$path . '/'];
            }
            // Check if store has secured url
            $path = str_replace('http', 'https', $path);
            if (array_key_exists($path . '/', $this->stores)) {
                return $this->stores[$path . '/'];
            }
        } while ($path = substr($path, 0, strrpos($path, '/')));

        return '';
    }

}
