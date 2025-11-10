<?php

namespace Cliomusetours\LaravelHealth\Http\Controllers;

use Cliomusetours\LaravelHealth\Runner\HealthRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HealthController
{
    public function __construct(
        protected HealthRunner $runner
    ) {
    }

    /**
     * Liveness probe - extremely fast check to verify app is alive.
     */
    public function live(): JsonResponse
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

    /**
     * Readiness probe - run all configured health checks.
     */
    public function ready(): JsonResponse
    {
        $results = $this->runner->runChecks();

        $httpStatus = $results['status'] === 'failed' ? 503 : 200;

        return response()->json($results, $httpStatus);
    }
}
