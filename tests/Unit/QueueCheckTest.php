<?php

use Cliomusetours\LaravelHealth\Checks\QueueCheck;

test('queue check passes when queue is operational', function () {
    config([
        'health.checks.' . QueueCheck::class => [
            'queues' => ['sync'],
            'threshold' => 100,
        ],
        'queue.default' => 'sync',
        'queue.connections.sync' => [
            'driver' => 'sync',
        ],
    ]);

    $check = new QueueCheck();
    $result = $check->run();

    expect($result['status'])->toBe('ok');
    expect($result['message'])->toContain('operational');
    expect($result['meta']['queues']['sync']['status'])->toBe('ok');
});

test('queue check supports multiple queues', function () {
    config([
        'health.checks.' . QueueCheck::class => [
            'queues' => ['sync', 'sync2'],
            'threshold' => 100,
        ],
        'queue.default' => 'sync',
        'queue.connections.sync' => [
            'driver' => 'sync',
        ],
        'queue.connections.sync2' => [
            'driver' => 'sync',
        ],
    ]);

    $check = new QueueCheck();
    $result = $check->run();

    expect($result['status'])->toBe('ok');
    expect($result['message'])->toContain('All 2 queues operational');
    expect($result['meta']['queues'])->toHaveKeys(['sync', 'sync2']);
    expect($result['meta']['queues']['sync']['status'])->toBe('ok');
    expect($result['meta']['queues']['sync2']['status'])->toBe('ok');
});
