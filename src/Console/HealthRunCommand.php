<?php

namespace Cliomusetours\LaravelHealth\Console;

use Cliomusetours\LaravelHealth\Runner\HealthRunner;
use Illuminate\Console\Command;

class HealthRunCommand extends Command
{
    protected $signature = 'health:run {--no-cache : Skip cache and run fresh checks}';

    protected $description = 'Run all health checks and display results';

    public function handle(HealthRunner $runner): int
    {
        $this->info('Running health checks...');
        $this->newLine();

        $useCache = !$this->option('no-cache');
        $results = $runner->runChecks($useCache);

        // Display overall status
        $statusColor = $results['status'] === 'ok' ? 'green' : 'red';
        $this->line("<fg=$statusColor>Overall Status: " . strtoupper($results['status']) . '</fg>');
        $this->line('Timestamp: ' . $results['timestamp']);

        if ($results['cached'] ?? false) {
            $this->line('<fg=yellow>Results from cache (cached at: ' . ($results['cached_at'] ?? 'unknown') . ')</fg>');
        }

        $this->newLine();

        // Display individual checks
        if (isset($results['checks']) && is_array($results['checks'])) {
            $this->table(
                ['Check', 'Status', 'Duration (ms)', 'Message'],
                collect($results['checks'])->map(function ($check, $name) {
                    $statusColor = match ($check['status']) {
                        'ok' => 'green',
                        'warning' => 'yellow',
                        'critical' => 'red',
                        default => 'white',
                    };

                    return [
                        $name,
                        "<fg=$statusColor>" . strtoupper($check['status']) . '</fg>',
                        $check['duration_ms'] ?? 0,
                        $check['message'] ?? '',
                    ];
                })->values()->toArray()
            );
        }

        $this->newLine();

        // Output JSON for piping/automation
        if ($this->output->isVerbose()) {
            $this->line('JSON Output:');
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
        }

        return $results['status'] === 'ok' ? Command::SUCCESS : Command::FAILURE;
    }
}
