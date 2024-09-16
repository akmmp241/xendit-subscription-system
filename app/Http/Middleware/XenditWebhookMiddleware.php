<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class XenditWebhookMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->header('x-callback-token') ||
            $request->header('x-callback-token') !== env('XENDIT_WEBHOOK_KEY'))
            throw new UnauthorizedHttpException('Invalid Callback Token');

        return $next($request);
    }
}
