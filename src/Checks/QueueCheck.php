<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Illuminate\Support\Facades\Queue;

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
        $connection = $this->config['connection'] ?? config('queue.default');
        $threshold = $this->config['threshold'] ?? 100;

        try {
            $queueSize = $this->getQueueSize($connection);
            $duration = (microtime(true) - $startTime) * 1000;

            $status = 'ok';
            $message = 'Queue is operational';

            if ($queueSize > $threshold) {
                $status = 'warning';
                $message = "Queue size ($queueSize) exceeds threshold ($threshold)";
            }

            return [
                'status' => $status,
                'duration_ms' => round($duration, 2),
                'message' => $message,
                'meta' => [
                    'connection' => $connection,
                    'size' => $queueSize,
                    'threshold' => $threshold,
                ],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'Queue check failed: ' . $e->getMessage(),
                'meta' => [
                    'connection' => $connection,
                    'error' => get_class($e),
                ],
            ];
        }
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
                    return 0; // Sync queue has no backlog
                default:
                    // For other drivers, we can't easily determine size
                    // Return 0 to indicate operational but unknown size
                    return 0;
            }
        } catch (\Throwable $e) {
            // If we can't get size, return 0 rather than failing
            return 0;
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
