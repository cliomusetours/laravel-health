<?php

use Cliomusetours\LaravelHealth\Checks\HttpServiceCheck;
use Illuminate\Support\Facades\Http;

test('http service check passes when endpoint is reachable', function () {
    Http::fake([
        'https://example.com' => Http::response('OK', 200),
    ]);

    config([
        'health.checks.' . HttpServiceCheck::class => [
            'url' => 'https://example.com',
            'method' => 'GET',
            'expected_status' => 200,
            'timeout_ms' => 5000,
        ],
    ]);

    $check = new HttpServiceCheck();
    $result = $check->run();

    expect($result['status'])->toBe('ok');
    expect($result['message'])->toContain('reachable');
    expect($result['meta']['status_code'])->toBe(200);
});

test('http service check returns warning when status code mismatches', function () {
    Http::fake([
        'https://example.com' => Http::response('Not Found', 404),
    ]);

    config([
        'health.checks.' . HttpServiceCheck::class => [
            'url' => 'https://example.com',
            'method' => 'GET',
            'expected_status' => 200,
            'timeout_ms' => 5000,
        ],
    ]);

    $check = new HttpServiceCheck();
    $result = $check->run();

    expect($result['status'])->toBe('warning');
    expect($result['message'])->toContain('Unexpected HTTP status');
});

test('http service check returns critical when request fails', function () {
    Http::fake(function () {
        throw new \Exception('Connection timeout');
    });

    config([
        'health.checks.' . HttpServiceCheck::class => [
            'url' => 'https://example.com',
            'method' => 'GET',
            'expected_status' => 200,
            'timeout_ms' => 5000,
        ],
    ]);

    $check = new HttpServiceCheck();
    $result = $check->run();

    expect($result['status'])->toBe('critical');
    expect($result['message'])->toContain('failed');
});
