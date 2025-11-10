<?php

namespace Cliomusetours\LaravelHealth\Contracts;

interface HealthCheck
{
    /**
     * Get the name of the health check.
     */
    public function name(): string;

    /**
     * Run the health check.
     *
     * @return array{status: string, duration_ms: float, message: string, meta: array}
     *
     * Status values: 'ok', 'warning', 'critical'
     */
    public function run(): array;
}
