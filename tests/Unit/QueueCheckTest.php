<?php

use Cliomusetours\LaravelHealth\Checks\QueueCheck;

test('queue check passes when queue is operational', function () {
    config([
        'health.checks.' . QueueCheck::class => [
            'connection' => 'sync',
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
    expect($result['meta']['connection'])->toBe('sync');
});
