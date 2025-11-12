<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Illuminate\Support\Facades\Cache;

class CacheCheck implements HealthCheck
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('health.checks.' . self::class, []);
    }

    public function name(): string
    {
        return 'cache';
    }

    public function run(): array
    {
        $startTime = microtime(true);
        $drivers = $this->config['drivers'] ?? ['redis'];
        $failedDrivers = [];
        $successfulDrivers = [];

        try {
            foreach ($drivers as $driver) {
                try {
                    if (! $this->isDriverConfigured($driver)) {
                        continue;
                    }

                    $store = Cache::store($driver);
                    $key = 'laravel_health_cache_check_' . $driver;
                    $value = 'test_' . time();

                    $store->put($key, $value, 60);

                    $retrieved = $store->get($key);

                    $store->forget($key);

                    if ($retrieved === $value) {
                        $successfulDrivers[] = $driver;
                    } else {
                        $failedDrivers[] = $driver;
                    }
                } catch (\Throwable $e) {
                    $failedDrivers[] = $driver . ' (' . $e->getMessage() . ')';
                }
            }

            $duration = (microtime(true) - $startTime) * 1000;

            if (count($failedDrivers) > 0) {
                return [
                    'status' => 'critical',
                    'duration_ms' => round($duration, 2),
                    'message' => 'Some cache drivers failed',
                    'meta' => [
                        'successful' => $successfulDrivers,
                        'failed' => $failedDrivers,
                    ],
                ];
            }

            return [
                'status' => 'ok',
                'duration_ms' => round($duration, 2),
                'message' => 'Cache is working',
                'meta' => [
                    'drivers' => $successfulDrivers,
                ],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'Cache check failed: ' . $e->getMessage(),
                'meta' => [
                    'error' => get_class($e),
                ],
            ];
        }
    }

    protected function isDriverConfigured(string $driver): bool
    {
        try {
            Cache::store($driver);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
