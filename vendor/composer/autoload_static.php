<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit84982da661e88dfc32a42a0c733bc95a
{
    public static $files = array (
        'b0655c4b47b25ec49f0e931fe41ab7a3' => __DIR__ . '/..' . '/phalapi/kernal/src/bootstrap.php',
        '5cab427b0519bb4ddb2f894b03d1d957' => __DIR__ . '/..' . '/phalapi/kernal/src/functions.php',
        '841780ea2e1d6545ea3a253239d59c05' => __DIR__ . '/..' . '/qiniu/php-sdk/src/Qiniu/functions.php',
        '3ee9e1a664528c598a238875f08b4f5e' => __DIR__ . '/../..' . '/src/App/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
        'Q' => 
        array (
            'Qiniu\\' => 6,
        ),
        'P' => 
        array (
            'PhalApi\\Task\\' => 13,
            'PhalApi\\Qiniu\\' => 14,
            'PhalApi\\NotORM\\' => 15,
            'PhalApi\\' => 8,
            'PHPSocketIO\\' => 12,
        ),
        'G' => 
        array (
            'GatewayWorker\\' => 14,
        ),
        'C' => 
        array (
            'Channel\\' => 8,
        ),
        'A' => 
        array (
            'App\\' => 4,
        ),
        'M' => 
        array (
            'Mobile\\' =>7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
        'Qiniu\\' => 
        array (
            0 => __DIR__ . '/..' . '/qiniu/php-sdk/src/Qiniu',
        ),
        'PhalApi\\Task\\' => 
        array (
            0 => __DIR__ . '/..' . '/phalapi/task/src',
        ),
        'PhalApi\\Qiniu\\' => 
        array (
            0 => __DIR__ . '/..' . '/phalapi/qiniu/src/qiniu',
            1 => __DIR__ . '/..' . '/phalapi/qiniu/src',
        ),
        'PhalApi\\NotORM\\' => 
        array (
            0 => __DIR__ . '/..' . '/phalapi/notorm/src',
        ),
        'PhalApi\\' => 
        array (
            0 => __DIR__ . '/..' . '/phalapi/kernal/src',
        ),
        'PHPSocketIO\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/phpsocket.io/src',
        ),
        'GatewayWorker\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Channel\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/channel/src',
        ),
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/App',
        ),
        'Mobile\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/Mobile',
        ),
    );

    public static $prefixesPsr0 = array (
        'Q' => 
        array (
            'Qiniu' => 
            array (
                0 => __DIR__ . '/..' . '/qiniu/qiniu/lib',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit84982da661e88dfc32a42a0c733bc95a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit84982da661e88dfc32a42a0c733bc95a::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit84982da661e88dfc32a42a0c733bc95a::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
