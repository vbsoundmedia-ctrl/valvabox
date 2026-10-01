<?php

use App\Http\Middleware\EnsureActive;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureInstalled;
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
        $middleware->web(prepend: [EnsureInstalled::class], append: [EnsureActive::class]);
        $middleware->alias(['admin' => EnsureAdmin::class]);
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        // cPanel hosts often sit behind a proxy that terminates SSL
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
