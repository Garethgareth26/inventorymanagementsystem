<?php
/**
 * Vercel Serverless Entrypoint for Laravel
 */

// Pastikan direktori sementara (tmp) tersedia untuk Laravel di Vercel (Read-Only Filesystem)
// Sembunyikan peringatan Deprecated dari PHP 8.4 agar tidak merusak respons JSON Livewire
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Paksa set env vars karena Vercel PHP kadang gagal mem-passing env dari vercel.json
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
        mkdir($dir, 0755, true);
    }
}

// Forward the request to Laravel's standard entrypoint
require __DIR__ . '/../public/index.php';
