<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class ProtectGameAccountResponses
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
