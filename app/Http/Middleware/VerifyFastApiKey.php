<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// app/Http/Middleware/VerifyFastApiKey.php


class VerifyFastApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = config('app.fast_api_key');

        $provided = $request->header('x-api-key');
        $apiKeyIsValid = (
            is_string($apiKey) && $apiKey !== ''
            && is_string($provided)
            && hash_equals($apiKey, $provided)
        );

        abort_if(!$apiKeyIsValid, 403, 'Access denied');

        return $next($request);
    }
}