# Getting Started with Laravel Health

This quick guide will help you get the Laravel Health package up and running in minutes.

## Installation

### Step 1: Install via Composer

```bash
composer require cliomusetours/laravel-health
```

The package will be auto-discovered by Laravel.

### Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=health-config
```

This creates `config/health.php` where you can customize checks and settings.

## Basic Usage

### Test the Endpoints

Once installed, the health endpoints are immediately available:

```bash
# Liveness check (fast)
curl http://localhost:8000/health/live

# Readiness check (comprehensive)
curl http://localhost:8000/health/ready
```

### Use Artisan Commands

Run health checks from the command line:

```bash
# Run all checks
php artisan health:run

# Run a specific check
php artisan health:check database

# List all configured checks
php artisan health:list
```

## Configuration

### Default Enabled Checks

By default, the following checks are enabled:
- ✅ Database connectivity
- ✅ Cache availability
- ✅ Queue status
- ✅ Filesystem operations

### Disable a Check

Edit `config/health.php`:

```php
'checks' => [
    DatabaseCheck::class => [
        'enabled' => false,  // Disable this check
    ],
]
```

### Configure Check Parameters

```php
'checks' => [
    DatabaseCheck::class => [
        'enabled' => true,
        'timeout' => 5,              // Max execution time
        'query_timeout_ms' => 1000,  // Warning threshold
    ],
]
```

### Change Route Prefix

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'status',  // Changes to /status/live and /status/ready
    'middleware' => ['api'],
]
```

### Add Authentication

```php
'routes' => [
    'middleware' => ['api', 'auth:sanctum'],  // Require authentication
]
```

### Configure Caching

```php
'cache' => [
    'enabled' => true,
    'ttl' => 60,  // Cache for 60 seconds
]
```

To disable caching:
```php
'cache' => [
    'enabled' => false,
]
```

## Creating Your First Custom Check

### Step 1: Create the Check Class

Create a new file `app/HealthChecks/CustomServiceCheck.php`:

```php
<?php

namespace App\HealthChecks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;

class CustomServiceCheck implements HealthCheck
{
    public function name(): string
    {
        return 'custom_service';
    }

    public function run(): array
    {
        $startTime = microtime(true);

        try {
            // Your check logic here
            $isHealthy = $this->checkMyService();
            
            $duration = (microtime(true) - $startTime) * 1000;

            if (!$isHealthy) {
                return [
                    'status' => 'critical',
                    'duration_ms' => round($duration, 2),
                    'message' => 'Service is unhealthy',
                    'meta' => [],
                ];
            }

            return [
                'status' => 'ok',
                'duration_ms' => round($duration, 2),
                'message' => 'Service is healthy',
                'meta' => ['service' => 'my-service'],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'critical',
                'duration_ms' => 0,
                'message' => 'Check failed: ' . $e->getMessage(),
                'meta' => ['error' => get_class($e)],
            ];
        }
    }

    private function checkMyService(): bool
    {
        // Implement your service check logic
        return true;
    }
}
```

### Step 2: Register the Check

Add it to `config/health.php`:

```php
'checks' => [
    // ... other checks
    
    \App\HealthChecks\CustomServiceCheck::class => [
        'enabled' => true,
        'timeout' => 5,
    ],
]
```

### Step 3: Test Your Check

```bash
php artisan health:check custom_service
```

## Kubernetes Integration

### Add to Your Deployment

Edit your `deployment.yaml`:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: my-laravel-app
spec:
  replicas: 3
  template:
    spec:
      containers:
      - name: app
        image: my-laravel-app:latest
        ports:
        - containerPort: 80
        
        # Liveness probe - is the app running?
        livenessProbe:
          httpGet:
            path: /health/live
            port: 80
          initialDelaySeconds: 10
          periodSeconds: 10
          timeoutSeconds: 2
          failureThreshold: 3
        
        # Readiness probe - is the app ready to serve?
        readinessProbe:
          httpGet:
            path: /health/ready
            port: 80
          initialDelaySeconds: 15
          periodSeconds: 10
          timeoutSeconds: 5
          successThreshold: 1
          failureThreshold: 3
```

### Apply the Configuration

```bash
kubectl apply -f deployment.yaml
```

### Monitor Health Status

```bash
# Watch pod status
kubectl get pods -w

# Check pod events
kubectl describe pod <pod-name>

# View health check logs
kubectl logs <pod-name> --tail=50
```

## Monitoring with Events

### Create an Event Listener

Create `app/Listeners/AlertOnHealthFailure.php`:

```php
<?php

namespace App\Listeners;

use Cliomusetours\LaravelHealth\Events\HealthCheckFailed;
use Illuminate\Support\Facades\Log;

class AlertOnHealthFailure
{
    public function handle(HealthCheckFailed $event): void
    {
        Log::error('Health check failed', [
            'check' => $event->checkName,
            'status' => $event->result['status'],
            'message' => $event->result['message'],
            'timestamp' => $event->timestamp->toIso8601String(),
        ]);

        // Send notification to Slack, PagerDuty, etc.
    }
}
```

### Register the Listener

In `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \Cliomusetours\LaravelHealth\Events\HealthCheckFailed::class => [
        \App\Listeners\AlertOnHealthFailure::class,
    ],
];
```

## Testing in Development

### Using PHP's Built-in Server

```bash
php artisan serve
```

Then test:
```bash
curl http://localhost:8000/health/ready
```

### Using Laravel Sail

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan health:run
curl http://localhost/health/ready
```

## Common Scenarios

### Scenario 1: Database-Only Check

```php
'checks' => [
    DatabaseCheck::class => [
        'enabled' => true,
    ],
    // Disable all other checks
    CacheCheck::class => ['enabled' => false],
    QueueCheck::class => ['enabled' => false],
    FilesystemCheck::class => ['enabled' => false],
]
```

### Scenario 2: External API Monitoring

```php
'checks' => [
    HttpServiceCheck::class => [
        'enabled' => true,
        'url' => 'https://api.stripe.com/healthcheck',
        'method' => 'GET',
        'expected_status' => 200,
        'timeout_ms' => 5000,
    ],
]
```

### Scenario 3: High-Performance Setup

```php
'cache' => [
    'enabled' => true,
    'ttl' => 300,  // Cache for 5 minutes
],
'timeout' => 3,  // Aggressive global timeout
```

## Troubleshooting

### Health Check Always Fails

1. Check Laravel logs: `storage/logs/laravel.log`
2. Run check manually: `php artisan health:check <check-name>`
3. Check configuration: `config/health.php`
4. Verify database/cache/queue connections

### Timeout Issues

Increase timeout in config:
```php
'timeout' => 30,  // Global timeout
'checks' => [
    DatabaseCheck::class => [
        'timeout' => 10,  // Per-check timeout
    ],
]
```

### 404 on Health Endpoints

1. Verify routes are enabled: `config/health.php`
2. Check route prefix configuration
3. Clear cache: `php artisan config:clear`
4. Verify middleware allows access

## Next Steps

- Read the full [README.md](README.md) for detailed documentation
- Review [CONTRIBUTING.md](CONTRIBUTING.md) to contribute
- Check [CHANGELOG.md](CHANGELOG.md) for version history
- Explore example custom checks in the repository

## Need Help?

- Open an issue on [GitHub](https://github.com/cliomusetours/laravel-health/issues)
- Check existing issues for solutions
- Review the comprehensive test suite for usage examples

**Happy health checking! 🏥**
