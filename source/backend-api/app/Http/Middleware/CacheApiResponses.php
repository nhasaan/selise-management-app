<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponses
{
    /**
     * Cache API responses for improved performance.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Generate a unique cache key based on the full URL and query parameters
        $cacheKey = 'api_response_' . sha1($request->fullUrl());

        // Check if we have a cached response
        if (Cache::has($cacheKey) && !app()->isLocal()) {
            return response()->json(
                Cache::get($cacheKey),
                200,
                ['X-API-Cache' => 'HIT']
            );
        }

        // Process the request
        $response = $next($request);

        // Cache the response data for a short period (30 seconds)
        // Only cache successful responses
        if ($response->getStatusCode() === 200) {
            $responseData = json_decode($response->getContent(), true);
            Cache::put($cacheKey, $responseData, now()->addSeconds(30));
        }

        return $response;
    }
}
