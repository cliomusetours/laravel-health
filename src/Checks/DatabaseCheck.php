<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Illuminate\Support\Facades\DB;

/**
 * Health check for database connectivity.
 */
class DatabaseCheck implements HealthCheck
{
    /**
     * Configuration for this check.
     * 
     * @var array
     */
    protected array $config;

    /**
     * DatabaseCheck Constructor.
     */
    public function __construct()
    {
        $this->config = config('health.checks.' . self::class, []);
    }

    /**
     * Get the name of the check.
     * 
     * @return string
     */
    public function name(): string
    {
        return 'database';
    }

    /**
     * Run the database health check.
     * 
     * @return array
     */
    public function run(): array
    {
        $startTime = microtime(true);

        try {
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
