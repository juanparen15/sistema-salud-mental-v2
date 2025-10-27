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
    ->withMiddleware(function (Middleware $middleware): void {
        // Configuración importante para Livewire
        $middleware->web(append: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);
        
        // Excluir las rutas de Livewire del CSRF
        $middleware->validateCsrfTokens(except: [
            'livewire/upload-file',
            'livewire/upload-file/*',
        ]);
        
        // Si estás detrás de un proxy/load balancer
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();