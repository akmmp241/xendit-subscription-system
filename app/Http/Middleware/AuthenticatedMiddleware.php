<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) return $next($request);

        throw new UnauthorizedException("Invalid Token");
    }
}
