<?php

use Cliomusetours\LaravelHealth\Checks\BusinessLogicCheck;

test('business logic check returns ok with default implementation', function () {
    $check = new BusinessLogicCheck();
    $result = $check->run();

    expect($result['status'])->toBe('ok');
    expect($result['message'])->toContain('passed');
    expect($result['meta']['note'])->toContain('placeholder');
});
