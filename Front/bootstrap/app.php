<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;              // Importuojame AuthManager
use Illuminate\Foundation\Configuration\Middleware;              // Importuojame savo tiekėją
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets; // Būtina importuoti SessionGuard, jei jį naudojate extend metode
                                                                 // Būtina importuoti Session Store, jei jį naudojate extend metode

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class, // Butinai prieš atvaizdavimo HandleInertiaRequests
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,

        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
// ->withAuth(function (AuthManager $auth) {
//     // Užregistruojame mūsų custom driver'į
//     $auth->extend('api_token', function ($app, $name, array $config) use ($auth) {
//         return new \Illuminate\Auth\SessionGuard($name, $auth->createUserProvider($config['provider']), $app['session.store']);
//     });
//     // Taip pat galite apibrėžti savo User Provider'į čia tiesiogiai
//     $auth->provider('auth_api_users', function ($app, array $config) {
//         return new AuthApiUserProvider();
//     });
// })
    ->create();
