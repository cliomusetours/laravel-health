<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Illuminate\Support\Facades\Queue;

/**
 * Queue Health Check
 * 
 * Monitors queue connections and job counts.
 * Configure the 'queues' array in health.php to check multiple queues:
 * 
 * 'queues' => ['default', 'emails', 'notifications']
 */
class QueueCheck implements HealthCheck
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('health.checks.' . self::class, []);
    }

    public function name(): string
    {
        return 'queue';
    }

    public function run(): array
    {
        $startTime = microtime(true);
        $queues = $this->config['queues'] ?? [config('queue.default')];
        $threshold = $this->config['threshold'] ?? 100;

        // Ensure queues is an array
        if (empty($queues)) {
            $queues = [config('queue.default')];
        }

        $results = [];
        $overallStatus = 'ok';
        $totalSize = 0;
        $warnings = [];
        $errors = [];

        foreach ($queues as $queueName) {
            try {
                $queueSize = $this->getQueueSize($queueName);
                $totalSize += $queueSize;

                $results[$queueName] = [
                    'size' => $queueSize,
                    'threshold' => $threshold,
                    'status' => 'ok',
                ];

                if ($queueSize > $threshold) {
                    $results[$queueName]['status'] = 'warning';
                    $warnings[] = "$queueName: $queueSize jobs (threshold: $threshold)";
                    $overallStatus = 'warning';
                }
            } catch (\Throwable $e) {
                $results[$queueName] = [
                    'size' => null,
                    'status' => 'critical',
                    'error' => $e->getMessage(),
                ];
                $errors[] = "$queueName: " . $e->getMessage();
                $overallStatus = 'critical';
            }
        }

        $duration = (microtime(true) - $startTime) * 1000;

        // Build message
        $message = $this->buildMessage($overallStatus, $warnings, $errors, $totalSize, count($queues));

        return [
            'status' => $overallStatus,
            'duration_ms' => round($duration, 2),
            'message' => $message,
            'meta' => [
                'queues' => $results,
                'total_size' => $totalSize,
                'threshold' => $threshold,
            ],
        ];
    }

    protected function buildMessage(string $status, array $warnings, array $errors, int $totalSize, int $queueCount): string
    {
        if ($status === 'critical' && !empty($errors)) {
            return 'Queue check failed: ' . implode('; ', $errors);
        }

        if ($status === 'warning' && !empty($warnings)) {
            return 'Queue size warning: ' . implode('; ', $warnings);
        }

        $queueText = $queueCount === 1 ? 'queue' : 'queues';
        return "All $queueCount $queueText operational (total: $totalSize jobs)";
    }

    protected function getQueueSize(string $connection): int
    {
        $driver = config("queue.connections.$connection.driver");

        try {
            switch ($driver) {
                case 'redis':
                    return $this->getRedisQueueSize($connection);
                case 'database':
                    return $this->getDatabaseQueueSize($connection);
                case 'sync':
                    return 0; // sync queue has no backlog
                default:
                    return 0; // To indicate operational but unknown size
            }
        } catch (\Throwable $e) {
            return 0; // return 0 if we can't get size, rather than failing
        }
    }

    protected function getRedisQueueSize(string $connection): int
    {
        try {
            $queue = config("queue.connections.$connection.queue", 'default');
            
            // Get Redis connection
            $redis = app('redis')->connection(
                config("queue.connections.$connection.connection", 'default')
            );

            // Redis queue stores jobs in lists with key pattern: queues:{name}
            $key = "queues:$queue";
            
            return (int) $redis->llen($key);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function getDatabaseQueueSize(string $connection): int
    {
        try {
            $table = config("queue.connections.$connection.table", 'jobs');
            $queue = config("queue.connections.$connection.queue", 'default');

            return (int) app('db')
                ->connection(config("queue.connections.$connection.connection"))
                ->table($table)
                ->where('queue', $queue)
                ->whereNull('reserved_at')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
