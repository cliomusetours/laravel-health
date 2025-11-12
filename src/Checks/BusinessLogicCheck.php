<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;

/**
 * A health check for custom business logic.
 * 
 * This is a placeholder check. Override this class to implement
 * your own business logic health checks.
 */
class BusinessLogicCheck implements HealthCheck
{
    /**
     * Configuration for this check.
     * 
     * @var array
     */
    protected array $config;

    /**
     * BusinessLogicCheck Constructor.
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
        return 'business_logic';
    }

    /**
     * Run the business logic health check.
     * 
     * @return array
     */
    public function run(): array
    {
        $startTime = microtime(true);

        try {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'ok',
                'duration_ms' => round($duration, 2),
                'message' => 'Placeholder business logic check passed.',
                'meta' => [
                    'note' => 'This is a placeholder check. Override this class to implement custom business logic checks.',
                ],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'Placeholder business logic check failed: ' . $e->getMessage(),
                'meta' => [
                    'error' => get_class($e),
                ],
            ];
        }
    }
}
