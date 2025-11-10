<?php

use Cliomusetours\LaravelHealth\Checks\DatabaseCheck;
use Illuminate\Support\Facades\DB;

test('liveness endpoint returns ok status', function () {
    $response = $this->getJson('/health/live');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
        ])
        ->assertJsonStructure([
            'status',
            'timestamp',
        ]);
});

test('liveness endpoint includes cache check when enabled', function () {
    config(['health.liveness.cache_ping' => true]);

    $response = $this->getJson('/health/live');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'timestamp',
            'cache',
        ]);
});

test('readiness endpoint returns ok when all checks pass', function () {
    // Mock successful database check
    DB::shouldReceive('select')->andReturn([]);
    DB::shouldReceive('getDriverName')->andReturn('sqlite');
    
    config([
        'health.checks' => [
            DatabaseCheck::class => [
                'enabled' => true,
                'timeout' => 5,
            ],
        ],
        'health.cache.enabled' => false,
    ]);

    $response = $this->getJson('/health/ready');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
        ])
        ->assertJsonStructure([
            'status',
            'timestamp',
            'checks',
            'cached',
        ]);
});

test('readiness endpoint returns 503 when a check fails critically', function () {
    // Mock failed database check
    DB::shouldReceive('select')->andThrow(new \Exception('Connection failed'));
    
    config([
        'health.checks' => [
            DatabaseCheck::class => [
                'enabled' => true,
                'timeout' => 5,
            ],
        ],
        'health.cache.enabled' => false,
    ]);

    $response = $this->getJson('/health/ready');

    $response->assertStatus(503)
        ->assertJson([
            'status' => 'failed',
        ]);
});

test('readiness endpoint respects caching', function () {
    DB::shouldReceive('select')->once()->andReturn([]);
    DB::shouldReceive('getDriverName')->andReturn('sqlite');
    
    config([
        'health.checks' => [
            DatabaseCheck::class => [
                'enabled' => true,
                'timeout' => 5,
            ],
        ],
        'health.cache.enabled' => true,
        'health.cache.ttl' => 60,
    ]);

    // First request - should hit database
    $response1 = $this->getJson('/health/ready');
    $response1->assertStatus(200);
    $response1->assertJson(['cached' => false]);

    // Second request - should use cache (DB::select should only be called once)
    $response2 = $this->getJson('/health/ready');
    $response2->assertStatus(200);
    $response2->assertJson(['cached' => true]);
});

test('readiness endpoint includes check details', function () {
    DB::shouldReceive('select')->andReturn([]);
    DB::shouldReceive('getDriverName')->andReturn('sqlite');
    
    config([
        'health.checks' => [
            DatabaseCheck::class => [
                'enabled' => true,
                'timeout' => 5,
            ],
        ],
        'health.cache.enabled' => false,
    ]);

    $response = $this->getJson('/health/ready');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'checks' => [
                'database' => [
                    'status',
                    'duration_ms',
                    'message',
                    'meta',
                ],
            ],
        ]);
});
