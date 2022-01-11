<?php

namespace CappasityTech\Magento3D\Model\Exceptions;

class CappasityRequestException
{
    public static function getMessage($exception)
    {
        if (in_array($exception->getCode(), ['401', '403'])) {
            return __('Invalid access token');
        }
        return $exception->getMessage();
    }
}
