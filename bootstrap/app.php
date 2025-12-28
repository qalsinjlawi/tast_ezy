<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // تسجيل alias للـ middleware الخاص بالأدوار
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // يمكنك إضافة middleware أخرى هنا لو أردت
        // مثال:
        // $middleware->append(\App\Http\Middleware\Something::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();