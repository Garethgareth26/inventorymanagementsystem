<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// VERCEL SERVERLESS ENVIRONMENT FIX
if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL')) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    
    $_ENV['VERCEL'] = '1';
    $_SERVER['VERCEL'] = '1';

    $storagePath = '/tmp/storage';
    
    $envs = [
        'VIEW_COMPILED_PATH' => "$storagePath/framework/views",
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'cookie',
        'LOG_CHANNEL' => 'stderr',
        'APP_SERVICES_CACHE' => "$storagePath/bootstrap/cache/services.php",
        'APP_PACKAGES_CACHE' => "$storagePath/bootstrap/cache/packages.php",
        'APP_CONFIG_CACHE' => "$storagePath/bootstrap/cache/config.php",
        'APP_ROUTES_CACHE' => "$storagePath/bootstrap/cache/routes-v7.php",
        'APP_EVENTS_CACHE' => "$storagePath/bootstrap/cache/events.php",
    ];

    foreach ($envs as $k => $v) {
        putenv("$k=$v");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }

    $directories = [
        "$storagePath/app",
        "$storagePath/framework/cache/data",
        "$storagePath/framework/sessions",
        "$storagePath/framework/testing",
        "$storagePath/framework/views",
        "$storagePath/logs",
        "$storagePath/bootstrap/cache",
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
