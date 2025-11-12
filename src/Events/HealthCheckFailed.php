<?php

namespace Cliomusetours\LaravelHealth\Events;

class HealthCheckFailed
{   
    /**
     * Create a new event instance.
     * 
     * @param string $checkName
     * @param array $result
     * @param \DateTimeInterface $timestamp
     */
    public function __construct(
        public string $checkName,
        public array $result,
        public \DateTimeInterface $timestamp
    ) {
    }
}
