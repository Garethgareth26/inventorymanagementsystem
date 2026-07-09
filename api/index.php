<?php
/**
 * Vercel Serverless Entrypoint for Laravel
 */

// Pastikan direktori sementara (tmp) tersedia untuk Laravel di Vercel (Read-Only Filesystem)
if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL'])) {
    // Sembunyikan peringatan Deprecated dari PHP 8.4 agar tidak merusak respons JSON Livewire
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    
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
}

// Forward the request to Laravel's standard entrypoint
require __DIR__ . '/../public/index.php';
