<?php

use App\Http\Middleware\EnsureFacturacionActiva;
use App\Http\Middleware\EnsureRol;
use App\Http\Middleware\EnsureSuscripcionActiva;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => EnsureRol::class,
            'suscripcion' => EnsureSuscripcionActiva::class,
            'facturacion' => EnsureFacturacionActiva::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
