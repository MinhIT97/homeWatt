<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\ThrottleAiAnalysis;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));

        $middleware->web(append: [
            LocaleMiddleware::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'throttle.ai' => ThrottleAiAnalysis::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
