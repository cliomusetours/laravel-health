<?php

namespace Cliomusetours\LaravelHealth\Events;

class HealthCheckStarted
{
    /**
     * Create a new event instance.
     * 
     * @param string $checkName
     * @param \DateTimeInterface $timestamp
     */
    public function __construct(
        public string $checkName,
        public \DateTimeInterface $timestamp
    ) {
    }
}
