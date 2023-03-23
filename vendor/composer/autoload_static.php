<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit087c5dc119b6d255a7606ba9dd25a7b4
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MarlonFreire\\App\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MarlonFreire\\App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit087c5dc119b6d255a7606ba9dd25a7b4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit087c5dc119b6d255a7606ba9dd25a7b4::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}