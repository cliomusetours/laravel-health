<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Health Check Routes
    |--------------------------------------------------------------------------
    |
    | Configure the routing for health check endpoints.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'health',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Liveness Probe Configuration
    |--------------------------------------------------------------------------
    |
    | Liveness probe should be extremely fast. Enable cache_ping only if
    | you want to verify cache connectivity in liveness checks.
    |
    */

    'liveness' => [
        'cache_ping' => false, // Set to true to include a lightweight cache check
    ],

    /*
    |--------------------------------------------------------------------------
    | Readiness Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache readiness check results for performance. Set TTL in seconds.
    | Set to 0 or null to disable caching.
    |
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 60, // Cache results for 60 seconds
        'key' => 'laravel_health_readiness_cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) for all checks to complete.
    | Individual checks that exceed this will be marked as critical.
    |
    */

    'timeout' => 10,

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    |
    | List of health check classes to run for readiness probes.
    | Each check must implement the HealthCheck contract.
    |
    */

    'checks' => [
        \Cliomusetours\LaravelHealth\Checks\DatabaseCheck::class => [
            'enabled' => true,
            'timeout' => 5,
            'query_timeout_ms' => 1000, // Alert if query takes longer than this
        ],

        \Cliomusetours\LaravelHealth\Checks\CacheCheck::class => [
            'enabled' => true,
            'timeout' => 3,
            'drivers' => ['redis', 'memcached', 'file'], // Check these cache drivers
        ],

        \Cliomusetours\LaravelHealth\Checks\QueueCheck::class => [
            'enabled' => true,
            'timeout' => 5,
            'connection' => null, // null = default connection
            'threshold' => 100, // Warning if queue size exceeds this
        ],

        \Cliomusetours\LaravelHealth\Checks\FilesystemCheck::class => [
            'enabled' => true,
            'timeout' => 3,
            'disk' => 'local', // Storage disk to check
        ],

        \Cliomusetours\LaravelHealth\Checks\HttpServiceCheck::class => [
            'enabled' => false, // Enable and configure for external API checks
            'timeout' => 10,
            'url' => env('HEALTH_CHECK_EXTERNAL_URL', 'https://example.com/api/ping'),
            'method' => 'GET',
            'expected_status' => 200,
            'timeout_ms' => 5000,
        ],

        \Cliomusetours\LaravelHealth\Checks\BusinessLogicCheck::class => [
            'enabled' => false, // Enable and customize for your business logic
            'timeout' => 5,
        ],
    ],
];
