<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureClinicaActive;
use App\Http\Middleware\EnsureUniqueSession;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'clinica.active' => EnsureClinicaActive::class,
            'permission' => CheckPermission::class,
            'unique.session' => EnsureUniqueSession::class,
        ]);

        $middleware->preventRequestForgery(except: [
            'webhooks/stripe',
            'webhooks/woovi',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->booted(function () {
        // Rate limiting: 5 tentativas por minuto por IP nas rotas de auth
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiting: 10 tentativas por minuto para recuperação de senha
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    })
    ->create();
