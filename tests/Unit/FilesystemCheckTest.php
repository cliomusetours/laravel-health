<?php

use Cliomusetours\LaravelHealth\Checks\FilesystemCheck;
use Illuminate\Support\Facades\Storage;

test('filesystem check passes when storage is operational', function () {
    Storage::fake('local');
    
    config(['health.checks.' . FilesystemCheck::class . '.disk' => 'local']);

    $check = new FilesystemCheck();
    $result = $check->run();

    expect($result['status'])->toBe('ok');
    expect($result['message'])->toContain('operational');
    expect($result['meta']['disk'])->toBe('local');
});

test('filesystem check returns critical when write fails', function () {
    Storage::shouldReceive('disk')
        ->with('local')
        ->andReturnSelf();
    
    Storage::shouldReceive('put')
        ->andReturn(false);

    config(['health.checks.' . FilesystemCheck::class . '.disk' => 'local']);

    $check = new FilesystemCheck();
    $result = $check->run();

    expect($result['status'])->toBe('critical');
    expect($result['message'])->toContain('failed');
});
