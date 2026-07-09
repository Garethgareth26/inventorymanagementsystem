<?php

use App\Http\Middleware\RequireCapability;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'capability' => RequireCapability::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "<div style='font-family: sans-serif; padding: 20px; background: #e8f5e9; color: #1b5e20; border-radius: 8px;'>";
            echo "<h2 style='margin-top:0;'>PRIMARY ERROR DETECTED!</h2>";
            echo "<strong>Message:</strong> " . $e->getMessage() . "<br><br>";
            echo "<strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine() . "<br><br>";
            echo "<strong>Stack Trace:</strong><br><pre style='background: #fff; padding: 10px; border: 1px solid #ccc; overflow: auto;'>" . $e->getTraceAsString() . "</pre>";
            echo "</div>";
            exit;
        });
    })->create();

if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL'])) {
    $app->useStoragePath('/tmp/storage');
}

return $app;
