<?php

namespace Cliomusetours\LaravelHealth\Runner;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Cliomusetours\LaravelHealth\Events\HealthCheckFailed;
use Cliomusetours\LaravelHealth\Events\HealthCheckPassed;
use Cliomusetours\LaravelHealth\Events\HealthCheckStarted;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;


class HealthRunner
{
    /** 
     * Check results
     * @var array<string, array> 
     */
    protected array $results = [];
    
    /**
     * HealthRunner Constructor.
     * @var array
     */
    protected array $config;

    /**
     * HealthRunner Constructor.
     */
    public function __construct(
        protected Dispatcher $events
    ) {
        $this->config = config('health', []);
    }

    /**
     * Run all enabled health checks.
     * 
     * @return array<string, array>
     */
    public function runChecks(): array
    {
        $cacheEnabled = $this->config['cache']['enabled'] ?? false;
        $cacheTtl = $this->config['cache']['ttl'] ?? 60;
        $cacheKey = 'laravel_health_checks';

        // Try to get cached results
        if ($cacheEnabled) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $cached['cached'] = true;
                return $cached;
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
        $response['cached'] = false;

        // Cache the results
        if ($cacheEnabled) {
            Cache::put($cacheKey, $response, $cacheTtl);
        }

        return $response;
    }

    /**
     * Run a single health check with timeout handling.
     * 
     * @param HealthCheck $check
     * @param array $config
     * @return array
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
     * 
     * @param string $checkName
     * @return array|null
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
     * 
     * @return array<string, mixed>
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
     * 
     * @return array<int, array<string, mixed>>
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
