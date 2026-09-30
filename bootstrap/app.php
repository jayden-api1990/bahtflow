<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $registerApiRoutes = function () {
                Route::middleware('api')
                    ->prefix('api')
                    ->group(base_path('routes/api.php'));
            };

            if ($domain = config('app.api_domain')) {
                Route::domain($domain)->group($registerApiRoutes);
            } else {
                $registerApiRoutes();
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'merchant.sign' => \App\Http\Middleware\VerifyMerchantSign::class,
            'merchant.ip' => \App\Http\Middleware\VerifyMerchantIp::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
