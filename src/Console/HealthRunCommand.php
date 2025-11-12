<?php

namespace Cliomusetours\LaravelHealth\Console;

use Cliomusetours\LaravelHealth\Runner\HealthRunner;
use Illuminate\Console\Command;

class HealthRunCommand extends Command
{
    protected $signature = 'health:run';

    protected $description = 'Run all health checks and display results';

    public function handle(HealthRunner $runner): int
    {
        $this->info('Running health checks.');
        $this->newLine();

        $results = $runner->runChecks();

        $this->line("Overall Status: " . strtoupper($results['status']));
        $this->line('Timestamp: ' . $results['timestamp']);

        $this->newLine();

        // Display individual checks
        if (isset($results['checks']) && is_array($results['checks'])) {
            $this->table(
                ['Check', 'Status', 'Duration (ms)', 'Message'],
                collect($results['checks'])->map(function ($check, $name) {
                    return [
                        $name,
                        strtoupper($check['status']),
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
