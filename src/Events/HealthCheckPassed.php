<?php

namespace Cliomusetours\LaravelHealth\Events;

class HealthCheckPassed
{
    public function __construct(
        public string $checkName,
        public array $result,
        public \DateTimeInterface $timestamp
    ) {
    }
}
