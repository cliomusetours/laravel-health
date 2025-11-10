<?php

namespace Cliomusetours\LaravelHealth\Runner;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Cliomusetours\LaravelHealth\Events\HealthCheckFailed;
use Cliomusetours\LaravelHealth\Events\HealthCheckPassed;
use Cliomusetours\LaravelHealth\Events\HealthCheckStarted;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;

class HealthRunner
{
    protected array $results = [];
    protected array $config;

    public function __construct(
        protected CacheRepository $cache,
        protected Dispatcher $events
    ) {
        $this->config = config('health', []);
    }

    /**
     * Run all enabled health checks.
     */
    public function runChecks(bool $useCache = true): array
    {
        $cacheEnabled = $this->config['cache']['enabled'] ?? false;
        $cacheTtl = $this->config['cache']['ttl'] ?? 60;
        $cacheKey = $this->config['cache']['key'] ?? 'laravel_health_readiness_cache';

        // Check cache first
        if ($useCache && $cacheEnabled && $cacheTtl > 0) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return array_merge($cached, [
                    'cached' => true,
                    'cached_at' => $cached['timestamp'] ?? null,
                ]);
            }
        }

        $this->results = [];
        $checks = $this->config['checks'] ?? [];

        foreach ($checks as $checkClass => $checkConfig) {
            if (!($checkConfig['enabled'] ?? true)) {
                continue;
            }

            try {
                $check = App::make($checkClass);

                if (!$check instanceof HealthCheck) {
                    continue;
                }

                $result = $this->runSingleCheck($check, $checkConfig);
                $this->results[$check->name()] = $result;
            } catch (\Throwable $e) {
                $this->results[$checkClass] = [
                    'status' => 'critical',
                    'duration_ms' => 0,
                    'message' => 'Failed to instantiate check: ' . $e->getMessage(),
                    'meta' => [],
                ];
            }
        }

        $response = $this->buildResponse();

        // Cache the results
        if ($useCache && $cacheEnabled && $cacheTtl > 0) {
            $this->cache->put($cacheKey, $response, $cacheTtl);
        }

        return array_merge($response, ['cached' => false]);
    }

    /**
     * Run a single health check with timeout handling.
     */
    protected function runSingleCheck(HealthCheck $check, array $config): array
    {
        $checkName = $check->name();
        $timeout = $config['timeout'] ?? $this->config['timeout'] ?? 10;

        $this->events->dispatch(new HealthCheckStarted($checkName, now()));

        try {
            // Use pcntl_alarm for timeout if available (Unix only)
            if (function_exists('pcntl_alarm') && function_exists('pcntl_signal')) {
                $timedOut = false;
                pcntl_signal(SIGALRM, function () use (&$timedOut) {
                    $timedOut = true;
                });
                pcntl_alarm($timeout);
            }

            $result = $check->run();

            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0); // Cancel alarm

                if ($timedOut ?? false) {
                    $result = [
                        'status' => 'critical',
                        'duration_ms' => $timeout * 1000,
                        'message' => 'Check timed out',
                        'meta' => [],
                    ];
                }
            }

            // Validate result structure
            $result = array_merge([
                'status' => 'ok',
                'duration_ms' => 0,
                'message' => '',
                'meta' => [],
            ], $result);

            // Dispatch appropriate event
            if ($result['status'] === 'ok') {
                $this->events->dispatch(new HealthCheckPassed($checkName, $result, now()));
            } else {
                $this->events->dispatch(new HealthCheckFailed($checkName, $result, now()));
            }

            return $result;
        } catch (\Throwable $e) {
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }

            $result = [
                'status' => 'critical',
                'duration_ms' => 0,
                'message' => 'Exception: ' . $e->getMessage(),
                'meta' => ['exception' => get_class($e)],
            ];

            $this->events->dispatch(new HealthCheckFailed($checkName, $result, now()));

            return $result;
        }
    }

    /**
     * Run a specific check by name.
     */
    public function runCheck(string $checkName): ?array
    {
        $checks = $this->config['checks'] ?? [];

        foreach ($checks as $checkClass => $checkConfig) {
            try {
                $check = App::make($checkClass);

                if (!$check instanceof HealthCheck) {
                    continue;
                }

                if ($check->name() === $checkName) {
                    return $this->runSingleCheck($check, $checkConfig);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Build the response with overall status.
     */
    protected function buildResponse(): array
    {
        $overallStatus = 'ok';

        foreach ($this->results as $result) {
            if ($result['status'] === 'critical') {
                $overallStatus = 'failed';
                break;
            }
        }

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'checks' => $this->results,
        ];
    }

    /**
     * Get all configured checks.
     */
    public function listChecks(): array
    {
        $checks = $this->config['checks'] ?? [];
        $list = [];

        foreach ($checks as $checkClass => $checkConfig) {
            try {
                $check = App::make($checkClass);

                if (!$check instanceof HealthCheck) {
                    continue;
                }

                $list[] = [
                    'name' => $check->name(),
                    'class' => $checkClass,
                    'enabled' => $checkConfig['enabled'] ?? true,
                ];
            } catch (\Throwable $e) {
                $list[] = [
                    'name' => class_basename($checkClass),
                    'class' => $checkClass,
                    'enabled' => $checkConfig['enabled'] ?? true,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $list;
    }
}
