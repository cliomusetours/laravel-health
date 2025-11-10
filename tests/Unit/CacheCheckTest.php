<?php

use Cliomusetours\LaravelHealth\Checks\CacheCheck;
use Illuminate\Support\Facades\Cache;

test('cache check passes when cache is working', function () {
    config(['health.checks.' . CacheCheck::class . '.drivers' => ['array']]);

    Cache::shouldReceive('store')
        ->with('array')
        ->andReturnSelf();
    
    Cache::shouldReceive('put')
        ->once()
        ->andReturn(true);
    
    Cache::shouldReceive('get')
        ->once()
        ->andReturn('test_' . time());
    
    Cache::shouldReceive('forget')
        ->once()
        ->andReturn(true);

    $check = new CacheCheck();
    $result = $check->run();

    expect($result['status'])->toBe('ok');
    expect($result['message'])->toContain('working');
});

test('cache check returns critical when cache fails', function () {
    config(['health.checks.' . CacheCheck::class . '.drivers' => ['redis']]);

    Cache::shouldReceive('store')
        ->with('redis')
        ->andThrow(new \Exception('Redis connection failed'));

    $check = new CacheCheck();
    $result = $check->run();

    expect($result['status'])->toBe('critical');
    expect($result['meta']['failed'])->toHaveCount(1);
});
