<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;

class BusinessLogicCheck implements HealthCheck
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('health.checks.' . self::class, []);
    }

    public function name(): string
    {
        return 'business_logic';
    }

    public function run(): array
    {
        $startTime = microtime(true);

        try {
            // TODO: Customize this check for your application's business logic
            // 
            // Example 1: Check if users table has records
            // $userCount = \App\Models\User::count();
            // if ($userCount === 0) {
            //     return [
            //         'status' => 'warning',
            //         'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            //         'message' => 'No users found in the system',
            //         'meta' => ['user_count' => 0],
            //     ];
            // }
            //
            // Example 2: Check if a critical setting exists
            // $setting = \App\Models\Setting::where('key', 'critical_feature_enabled')->first();
            // if (!$setting || !$setting->value) {
            //     return [
            //         'status' => 'critical',
            //         'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            //         'message' => 'Critical feature is not enabled',
            //         'meta' => [],
            //     ];
            // }
            //
            // Example 3: Check if required data is present
            // $requiredData = \App\Models\Configuration::where('required', true)->count();
            // $totalRequired = 5;
            // if ($requiredData < $totalRequired) {
            //     return [
            //         'status' => 'warning',
            //         'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            //         'message' => 'Missing required configuration data',
            //         'meta' => [
            //             'found' => $requiredData,
            //             'required' => $totalRequired,
            //         ],
            //     ];
            // }

            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'ok',
                'duration_ms' => round($duration, 2),
                'message' => 'Business logic check passed (default implementation)',
                'meta' => [
                    'note' => 'This is a placeholder. Customize for your application.',
                ],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'Business logic check failed: ' . $e->getMessage(),
                'meta' => [
                    'error' => get_class($e),
                ],
            ];
        }
    }
}
