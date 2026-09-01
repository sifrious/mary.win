<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sifrious\AccountsClient\Exceptions\ProductAccessDenied;
use Sifrious\AccountsClient\Laravel\RequireProductEntitlement;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['zahir.entitlement' => RequireProductEntitlement::class]);

        // Laravel re-sorts middleware by its priority list at runtime, and
        // Authenticate is on that list while this is not — so listing them in
        // order on the route group does not guarantee that order. Without this,
        // a signed-out visitor is refused by the entitlement gate before auth
        // runs, and gets a flat denial instead of the sign-in page.
        $middleware->appendToPriorityList(Authenticate::class, RequireProductEntitlement::class);

        // A lapsed session gets an explanation; a first-time guest just gets
        // the sign-in page.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->session()->has('zahir.account_id')
            ? route('auth.problem', ['state' => 'session_expired'])
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The package answers JSON requests itself and leaves browsers to us.
        $exceptions->render(fn (ProductAccessDenied $denied, Request $request) => $request->expectsJson()
            ? null
            : redirect()->route('auth.problem', ['state' => $denied->outcome->value]));
    })->create();
