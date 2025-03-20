<?php

use App\Console\Commands\SendInvoiceToBosnet;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('app:send-invoice-to-bosnet')->everyFiveMinutes();
        $schedule->command('app:sync-intransit')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
