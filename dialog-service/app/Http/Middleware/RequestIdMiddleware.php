<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $requestId = $request->header('X-Request-Id');
        if (empty($requestId)) {
            $requestId = Str::uuid()->toString();
        }

        // Store request ID for use in logs
        $request->headers->set('X-Request-Id', $requestId);

        // Add request ID to response headers
        $response = $next($request);
        $response->header('X-Request-Id', $requestId);

        // Log request
        Log::info('Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $requestId,
        ]);

        return $response;
    }
}
