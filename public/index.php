<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// VERCEL SERVERLESS ENVIRONMENT FIX
// Kita eksekusi di public/index.php agar 100% dijamin jalan meskipun Vercel mem-bypass api/index.php
if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    
    $_ENV['VERCEL'] = '1';
    $_SERVER['VERCEL'] = '1';
    putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
    $_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
    $_SERVER['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
    
    putenv('CACHE_STORE=array');
    $_ENV['CACHE_STORE'] = 'array';
    
    putenv('SESSION_DRIVER=cookie');
    $_ENV['SESSION_DRIVER'] = 'cookie';
    
    putenv('LOG_CHANNEL=stderr');
    $_ENV['LOG_CHANNEL'] = 'stderr';
    
    $storagePath = '/tmp/storage';
    $directories = [
        "$storagePath/app",
        "$storagePath/framework/cache/data",
        "$storagePath/framework/sessions",
        "$storagePath/framework/testing",
        "$storagePath/framework/views",
        "$storagePath/logs",
    ];

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
