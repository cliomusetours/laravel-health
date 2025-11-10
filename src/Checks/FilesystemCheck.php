<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;
use Illuminate\Support\Facades\Storage;

class FilesystemCheck implements HealthCheck
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('health.checks.' . self::class, []);
    }

    public function name(): string
    {
        return 'filesystem';
    }

    public function run(): array
    {
        $startTime = microtime(true);
        $disk = $this->config['disk'] ?? 'local';

        try {
            $storage = Storage::disk($disk);
            $testFile = '.health_check_' . time() . '.txt';
            $testContent = 'Laravel Health Check - ' . now()->toIso8601String();

            // Write
            $writeSuccess = $storage->put($testFile, $testContent);

            if (!$writeSuccess) {
                throw new \RuntimeException('Failed to write test file');
            }

            // Read
            $readContent = $storage->get($testFile);

            if ($readContent !== $testContent) {
                throw new \RuntimeException('File content mismatch');
            }

            // Delete
            $deleteSuccess = $storage->delete($testFile);

            if (!$deleteSuccess) {
                throw new \RuntimeException('Failed to delete test file');
            }

            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'ok',
                'duration_ms' => round($duration, 2),
                'message' => 'Filesystem is operational',
                'meta' => [
                    'disk' => $disk,
                    'driver' => config("filesystems.disks.$disk.driver"),
                ],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            // Try to clean up test file if it exists
            try {
                if (isset($testFile) && Storage::disk($disk)->exists($testFile)) {
                    Storage::disk($disk)->delete($testFile);
                }
            } catch (\Throwable $cleanupException) {
                // Ignore cleanup errors
            }

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'Filesystem check failed: ' . $e->getMessage(),
                'meta' => [
                    'disk' => $disk,
                    'error' => get_class($e),
                ],
            ];
        }
    }
}
