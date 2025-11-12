<?php

namespace Cliomusetours\LaravelHealth\Http\Controllers;

use Cliomusetours\LaravelHealth\Runner\HealthRunner;
use Illuminate\Http\JsonResponse;

class ReadinessController
{   
    /**
     * Create a new controller instance.
     * 
     * @param HealthRunner $runner
     */
    public function __construct(
        protected HealthRunner $runner
    ) {
    }

    /**
     * Readiness probe - run all configured health checks.
     * 
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $results = $this->runner->runChecks();

        $httpStatus = $results['status'] === 'failed' ? 503 : 200;

        return response()->json($results, $httpStatus);
    }
}
