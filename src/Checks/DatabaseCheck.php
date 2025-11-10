<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Illuminate\Support\Facades\DB;

class DatabaseCheck implements HealthCheck
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('health.checks.' . self::class, []);
    }

    public function name(): string
    {
        return 'database';
    }

    public function run(): array
    {
        $startTime = microtime(true);

        try {
            // Simple SELECT 1 query to verify database connectivity
            DB::select('SELECT 1');

            $duration = (microtime(true) - $startTime) * 1000;
            $queryTimeoutMs = $this->config['query_timeout_ms'] ?? 1000;

            $status = 'ok';
            $message = 'Database connection successful';

            if ($duration > $queryTimeoutMs) {
                $status = 'warning';
                $message = 'Database query is slower than expected';
            }

            return [
                'status' => $status,
                'duration_ms' => round($duration, 2),
                'message' => $message,
                'meta' => [
                    'connection' => config('database.default'),
                    'driver' => DB::getDriverName(),
                ],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'meta' => [
                    'connection' => config('database.default'),
                    'error' => get_class($e),
                ],
            ];
        }
    }
}
