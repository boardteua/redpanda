<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestLogContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        Log::shareContext([
            'request_id' => $requestId,
            'http_method' => $request->method(),
            'path' => '/'.$request->path(),
        ]);

        if (config('observability.log_http_summary') && ! $request->is('health/*', 'up')) {
            Log::info('http.request.summary', [
                'route' => $request->route()?->getName(),
            ]);
        }

        return $next($request);
    }
}
