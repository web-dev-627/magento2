<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit324d6ec3471636e31ca231ed8189c7b8
{
    public static $files = array (
        '274990ea80ee722c9a4bd5156f58583a' => __DIR__ . '/../..' . '/registration.php',
        'da84b75f5f3a66c959e5c515148a259f' => __DIR__ . '/../..' . '/multistore.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Wws\\Multistore\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Wws\\Multistore\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit324d6ec3471636e31ca231ed8189c7b8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit324d6ec3471636e31ca231ed8189c7b8::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}