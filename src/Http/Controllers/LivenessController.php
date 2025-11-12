<?php

namespace Cliomusetours\LaravelHealth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LivenessController
{
    /**
     * Liveness probe - extremely fast check to verify app is alive.
     */
    public function __invoke(): JsonResponse
    {
        $config = config('health.liveness', []);
        $cachePing = $config['cache_ping'] ?? false;

        $response = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ];

        // Optional lightweight cache ping
        if ($cachePing) {
            try {
                Cache::get('laravel_health_liveness_ping');
                $response['cache'] = 'ok';
            } catch (\Throwable $e) {
                $response['cache'] = 'failed';
            }
        }

        return response()->json($response, 200);
    }
}
