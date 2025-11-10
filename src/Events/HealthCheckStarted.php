<?php

namespace Cliomusetours\LaravelHealth\Events;

class HealthCheckStarted
{
    public function __construct(
        public string $checkName,
        public \DateTimeInterface $timestamp
    ) {
    }
}
