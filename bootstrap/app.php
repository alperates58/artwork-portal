<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureApiUserIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LogSlowRequests;
use App\Http\Middleware\RedirectIfSetupComplete;
use App\Http\Middleware\RedirectToSetupIfNotComplete;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__ . '/../routes/web.php',
            __DIR__ . '/../routes/setup.php',
        ],
        api:      __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            RedirectToSetupIfNotComplete::class,
            EnsureUserIsActive::class,
            LogSlowRequests::class,
        ]);
        $middleware->alias([
            'role'       => CheckRole::class,
            'active'     => EnsureUserIsActive::class,
            'active.api' => EnsureApiUserIsActive::class,
            'setup.lock' => RedirectIfSetupComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
