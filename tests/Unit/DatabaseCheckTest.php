<?php

use Cliomusetours\LaravelHealth\Checks\DatabaseCheck;
use Illuminate\Support\Facades\DB;

test('database check passes when database is connected', function () {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andReturn([]);
    
    DB::shouldReceive('getDriverName')
        ->andReturn('sqlite');

    $check = new DatabaseCheck();
    $result = $check->run();

    expect($result['status'])->toBe('ok');
    expect($result['message'])->toContain('successful');
    expect($result['duration_ms'])->toBeGreaterThan(0);
});

test('database check returns critical when database fails', function () {
    DB::shouldReceive('select')
        ->once()
        ->with('SELECT 1')
        ->andThrow(new \Exception('Connection refused'));

    $check = new DatabaseCheck();
    $result = $check->run();

    expect($result['status'])->toBe('critical');
    expect($result['message'])->toContain('failed');
});

test('database check returns warning when query is slow', function () {
    // Mock a slow query
    DB::shouldReceive('select')
        ->once()
        ->andReturnUsing(function () {
            usleep(2000000); // 2 seconds
            return [];
        });
    
    DB::shouldReceive('getDriverName')
        ->andReturn('sqlite');

    config(['health.checks.' . DatabaseCheck::class . '.query_timeout_ms' => 1000]);

    $check = new DatabaseCheck();
    $result = $check->run();

    expect($result['status'])->toBe('warning');
    expect($result['message'])->toContain('slower than expected');
});
