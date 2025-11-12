<?php

namespace Cliomusetours\LaravelHealth\Http\Controllers;

use Cliomusetours\LaravelHealth\Runner\HealthRunner;
use Illuminate\Http\JsonResponse;

class ReadinessController
{
    public function __construct(
        protected HealthRunner $runner
    ) {
    }

    /**
     * Readiness probe - run all configured health checks.
     */
    public function __invoke(): JsonResponse
    {
        $results = $this->runner->runChecks();

        $httpStatus = $results['status'] === 'failed' ? 503 : 200;

        return response()->json($results, $httpStatus);
    }
}
