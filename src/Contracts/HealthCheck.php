<?php

namespace Cliomusetours\LaravelHealth\Contracts;

interface HealthCheck
{
    /**
     * Get the name of the health check.
     * 
     * @return string
     */
    public function name(): string;

    /**
     * Run the health check.
     *
     * @return array
     */
    public function run(): array;
}
