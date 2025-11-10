<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Illuminate\Support\Facades\Http;

class HttpServiceCheck implements HealthCheck
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('health.checks.' . self::class, []);
    }

    public function name(): string
    {
        return 'http_service';
    }

    public function run(): array
    {
        $startTime = microtime(true);
        $url = $this->config['url'] ?? 'https://example.com';
        $method = strtolower($this->config['method'] ?? 'GET');
        $expectedStatus = $this->config['expected_status'] ?? 200;
        $timeoutMs = $this->config['timeout_ms'] ?? 5000;
        $timeoutSeconds = $timeoutMs / 1000;

        try {
            $response = Http::timeout($timeoutSeconds)->$method($url);

            $duration = (microtime(true) - $startTime) * 1000;
            $actualStatus = $response->status();

            if ($actualStatus === $expectedStatus) {
                return [
                    'status' => 'ok',
                    'duration_ms' => round($duration, 2),
                    'message' => 'HTTP service is reachable',
                    'meta' => [
                        'url' => $url,
                        'status_code' => $actualStatus,
                        'response_time_ms' => round($duration, 2),
                    ],
                ];
            }

            return [
                'status' => 'warning',
                'duration_ms' => round($duration, 2),
                'message' => "Unexpected HTTP status: $actualStatus (expected $expectedStatus)",
                'meta' => [
                    'url' => $url,
                    'status_code' => $actualStatus,
                    'expected_status' => $expectedStatus,
                ],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'HTTP service check failed: ' . $e->getMessage(),
                'meta' => [
                    'url' => $url,
                    'error' => get_class($e),
                ],
            ];
        }
    }
}
