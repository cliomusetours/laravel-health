<?php

namespace Cliomusetours\LaravelHealth\Console;

use Cliomusetours\LaravelHealth\Runner\HealthRunner;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check {check : The name of the health check to run}';

    protected $description = 'Run a specific health check';

    public function handle(HealthRunner $runner): int
    {
        $checkName = $this->argument('check');

        $this->info("Running health check: $checkName");
        $this->newLine();

        $result = $runner->runCheck($checkName);

        if ($result === null) {
            $this->error("Health check '$checkName' not found.");
            $this->newLine();
            $this->info('Available checks:');
            
            foreach ($runner->listChecks() as $check) {
                $this->line('  - ' . $check['name']);
            }

            return Command::FAILURE;
        }

        $this->line("Status: " . strtoupper($result['status']));
        $this->line('Duration: ' . ($result['duration_ms'] ?? 0) . ' ms');
        $this->line('Message: ' . ($result['message'] ?? 'No message'));

        if (!empty($result['meta'])) {
            $this->newLine();
            $this->line('Metadata:');
            foreach ($result['meta'] as $key => $value) {
                $valueStr = is_array($value) ? json_encode($value) : (string) $value;
                $this->line("  $key: $valueStr");
            }
        }

        $this->newLine();

        if ($this->output->isVerbose()) {
            $this->line('JSON Output:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        }

        return $result['status'] === 'ok' ? Command::SUCCESS : Command::FAILURE;
    }
}
