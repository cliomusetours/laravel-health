<?php

namespace Cliomusetours\LaravelHealth\Console;

use Cliomusetours\LaravelHealth\Runner\HealthRunner;
use Illuminate\Console\Command;

class HealthListCommand extends Command
{
    protected $signature = 'health:list';

    protected $description = 'List all configured health checks';

    public function handle(HealthRunner $runner): int
    {
        $this->info('Configured Health Checks');
        $this->newLine();

        $checks = $runner->listChecks();

        if (empty($checks)) {
            $this->warn('No health checks configured.');
            return Command::SUCCESS;
        }

        $this->table(
            ['Name', 'Class', 'Enabled', 'Status'],
            collect($checks)->map(function ($check) {
                $enabledText = ($check['enabled'] ?? true) ? 'Yes' : 'No';
                $status = isset($check['error']) ? 'Error: ' . $check['error']  : 'OK';

                return [
                    $check['name'],
                    $check['class'],
                    $enabledText,
                    $status,
                ];
            })->toArray()
        );

        $this->newLine();

        return Command::SUCCESS;
    }
}
