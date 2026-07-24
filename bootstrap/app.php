<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\NoCache;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            LogActivity::class,
            NoCache::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
            'no.cache' => NoCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
