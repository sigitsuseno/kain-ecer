<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            // Key 'role' adalah nama yang kita panggil di route
            // Value adalah path lengkap ke class middleware-nya
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            '/print-thermal',
            '/ecer/penjualan/print-thermal',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
