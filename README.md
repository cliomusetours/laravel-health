# Laravel Health

A minimal, production-ready Laravel package providing liveness and readiness health-check endpoints container orchestration platforms. No UI, just JSON.

## Features

- **Two HTTP Endpoints**:
  - `GET /health/live` - Fast liveness probe (app is running)
  - `GET /health/ready` - Readiness probe (app is ready to serve traffic)
- **Built-in Health Checks**:
  - Database connectivity and query latency
  - Cache availability (Redis, Memcached, File)
  - Queue worker status and backlog monitoring
  - Filesystem storage operations
  - External HTTP service probes
  - Business logic checks (customizable)
- **Production Features**:
  - Response caching with configurable TTL
  - Per-check and global timeouts
  - Event dispatching for monitoring integrations
  - JSON-only output
  - HTTP 503 status on critical failures
- **Developer Experience**:
  - Artisan commands for testing checks
  - Easy to extend with custom checks
  - Comprehensive test suite
  - Support for Laravel 9, 10, and 11

## Requirements

- PHP `8.1` or higher
- Laravel `9.*`, `10.*`, `11.*` or `12.*`

## Installation

Install the package via Composer:

```bash
composer require cliomusetours/laravel-health
```

The service provider will be automatically registered via Laravel's package discovery.

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=health-config
```

This creates `config/health.php` where you can configure checks, routes, and more.

## Usage

### Quick Start

Once installed, the health check endpoints are immediately available:

```bash
curl http://localhost:8000/health/live

curl http://localhost:8000/health/ready
```

### Liveness Endpoint

The liveness endpoint (`/health/live`) is designed to be extremely fast and simply verifies that the application can boot and respond to requests.

**Response Example:**
```json
{
  "status": "ok",
  "timestamp": "2025-11-10T12:34:56+00:00"
}
```

**Configuration:**
```php
// config/health.php
'liveness' => [
    'cache_ping' => false, // Optionally check cache connectivity
],
```

### Readiness Endpoint

The readiness endpoint (`/health/ready`) runs all enabled health checks and returns detailed results.

**Response Example (Success - 200 OK):**
```json
{
  "status": "ok",
  "timestamp": "2025-11-10T12:34:56+00:00",
  "cached": false,
  "checks": {
    "database": {
      "status": "ok",
      "duration_ms": 12.45,
      "message": "Database connection successful",
      "meta": {
        "connection": "mysql",
        "driver": "mysql"
      }
    },
    "cache": {
      "status": "ok",
      "duration_ms": 5.23,
      "message": "Cache is working",
      "meta": {
        "drivers": ["redis"]
      }
    }
  }
}
```

**Response Example (Failure - 503 Service Unavailable):**
```json
{
  "status": "failed",
  "timestamp": "2025-11-10T12:34:56+00:00",
  "cached": false,
  "checks": {
    "database": {
      "status": "critical",
      "duration_ms": 8.12,
      "message": "Database connection failed: Connection refused",
      "meta": {
        "connection": "mysql",
        "error": "PDOException"
      }
    }
  }
}
```

### Status Levels

Each check returns one of three status levels:

- **`ok`** - Check passed successfully
- **`warning`** - Check passed but with degraded performance or concerns
- **`critical`** - Check failed, service is not ready

The overall readiness status is:
- **`ok`** if all checks are `ok` or `warning`
- **`failed`** if any check is `critical` (returns HTTP 503)

## Built-in Health Checks

### Database Check

Verifies database connectivity by executing a simple `SELECT 1` query.

```php
// config/health.php
\Cliomusetours\LaravelHealth\Checks\DatabaseCheck::class => [
    'enabled' => true,
    'timeout' => 5,
    'query_timeout_ms' => 1000, // Warning if query exceeds this
],
```

### Cache Check

Tests cache read, write, and delete operations on configured drivers.

```php
\Cliomusetours\LaravelHealth\Checks\CacheCheck::class => [
    'enabled' => true,
    'timeout' => 3,
    'drivers' => ['redis', 'memcached', 'file'],
],
```

### Queue Check

Monitors queue connection and checks backlog size.

```php
\Cliomusetours\LaravelHealth\Checks\QueueCheck::class => [
    'enabled' => true,
    'timeout' => 5,
    'connection' => null, // null = default connection
    'threshold' => 100, // Warning if queue size exceeds this
],
```

**Supported queue drivers**: `redis`, `database`, `sync`

### Filesystem Check

Verifies storage disk by performing write, read, and delete operations.

```php
\Cliomusetours\LaravelHealth\Checks\FilesystemCheck::class => [
    'enabled' => true,
    'timeout' => 3,
    'disk' => 'local',
],
```

### HTTP Service Check

Probes external HTTP services/APIs.

```php
\Cliomusetours\LaravelHealth\Checks\HttpServiceCheck::class => [
    'enabled' => false, // Enable when needed
    'timeout' => 10,
    'url' => env('HEALTH_CHECK_EXTERNAL_URL', 'https://api.example.com/ping'),
    'method' => 'GET',
    'expected_status' => 200,
    'timeout_ms' => 5000,
],
```

### Business Logic Check

Placeholder for application-specific business logic checks.

```php
\Cliomusetours\LaravelHealth\Checks\BusinessLogicCheck::class => [
    'enabled' => false, // Enable and customize
    'timeout' => 5,
],
```

**Example customization** (edit `src/Checks/BusinessLogicCheck.php` or create your own):
```php
public function run(): array
{
    $userCount = \App\Models\User::count();
    
    if ($userCount === 0) {
        return [
            'status' => 'warning',
            'duration_ms' => 10.5,
            'message' => 'No users in system',
            'meta' => ['user_count' => 0],
        ];
    }
    
    return [
        'status' => 'ok',
        'duration_ms' => 10.5,
        'message' => 'Business logic check passed',
        'meta' => ['user_count' => $userCount],
    ];
}
```

## Creating Custom Health Checks

### Step 1: Create a Check Class

Create a new class implementing the `HealthCheck` contract:

```php
<?php

namespace App\HealthChecks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;

class MyCustomCheck implements HealthCheck
{
    public function name(): string
    {
        return 'my_custom_check';
    }

    public function run(): array
    {
        $startTime = microtime(true);

        try {
            // Your custom logic here
            $result = $this->performCheck();
            
            $duration = (microtime(true) - $startTime) * 1000;

            if (!$result) {
                return [
                    'status' => 'critical',
                    'duration_ms' => round($duration, 2),
                    'message' => 'Custom check failed',
                    'meta' => [],
                ];
            }

            return [
                'status' => 'ok',
                'duration_ms' => round($duration, 2),
                'message' => 'Custom check passed',
                'meta' => ['some_data' => 'value'],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'Exception: ' . $e->getMessage(),
                'meta' => ['error' => get_class($e)],
            ];
        }
    }

    private function performCheck(): bool
    {
        // Your check logic
        return true;
    }
}
```

### Step 2: Register the Check

Add your custom check to `config/health.php`:

```php
'checks' => [
    // ... existing checks
    
    \App\HealthChecks\MyCustomCheck::class => [
        'enabled' => true,
        'timeout' => 5,
        // Any custom config parameters
    ],
],
```

### Step 3: Access Configuration in Your Check

```php
public function __construct()
{
    $this->config = config('health.checks.' . self::class, []);
}

public function run(): array
{
    $customValue = $this->config['custom_parameter'] ?? 'default';
    // Use configuration in your check logic
}
```

## Artisan Commands

### Run All Health Checks

```bash
php artisan health:run
```

Options:
- `--no-cache` - Skip cache and run fresh checks

Output:
```
Running health checks...

Overall Status: OK
Timestamp: 2025-11-10T12:34:56+00:00

┌──────────┬────────┬──────────────┬─────────────────────────────┐
│ Check    │ Status │ Duration (ms)│ Message                     │
├──────────┼────────┼──────────────┼─────────────────────────────┤
│ database │ OK     │ 12.45        │ Database connection success │
│ cache    │ OK     │ 5.23         │ Cache is working            │
└──────────┴────────┴──────────────┴─────────────────────────────┘
```

### Run a Specific Check

```bash
php artisan health:check database
```

### List All Configured Checks

```bash
php artisan health:list
```

## Configuration Reference

### Routes

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'health',
    'middleware' => ['api'],
],
```

**Customization example:**
```php
'routes' => [
    'enabled' => true,
    'prefix' => 'status', // Changes to /status/live and /status/ready
    'middleware' => ['api', 'auth:sanctum'], // Add authentication
],
```

### Caching

```php
'cache' => [
    'enabled' => true,
    'ttl' => 60, // Cache readiness results for 60 seconds
    'key' => 'laravel_health_readiness_cache',
],
```

Set `enabled` to `false` or `ttl` to `0` to disable caching.

### Timeouts

```php
'timeout' => 10, // Global timeout for all checks in seconds
```

Individual checks can override this with their own `timeout` configuration.

## Events

The package dispatches events that you can listen to for monitoring and alerting:

### Available Events

- `Cliomusetours\LaravelHealth\Events\HealthCheckStarted`
- `Cliomusetours\LaravelHealth\Events\HealthCheckPassed`
- `Cliomusetours\LaravelHealth\Events\HealthCheckFailed`

### Example Listener

```php
<?php

namespace App\Listeners;

use Cliomusetours\LaravelHealth\Events\HealthCheckFailed;
use Illuminate\Support\Facades\Log;

class LogFailedHealthCheck
{
    public function handle(HealthCheckFailed $event): void
    {
        Log::error('Health check failed', [
            'check' => $event->checkName,
            'result' => $event->result,
            'timestamp' => $event->timestamp->toIso8601String(),
        ]);

        // Send notification, trigger alert, etc.
    }
}
```

Register in `EventServiceProvider`:
```php
protected $listen = [
    \Cliomusetours\LaravelHealth\Events\HealthCheckFailed::class => [
        \App\Listeners\LogFailedHealthCheck::class,
    ],
];
```

## Docker Swarm Integration

### Basic Health Check Configuration

Docker Swarm supports built-in health checks that can use your Laravel Health endpoints:

```yaml
version: '3.8'

services:
  laravel-app:
    image: laravel-app:latest
    deploy:
      replicas: 3
      update_config:
        parallelism: 1
        delay: 10s
        order: start-first
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health/ready"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    ports:
      - "80:80"
    networks:
      - app-network

networks:
  app-network:
    driver: overlay
```

### Advanced Configuration with Multiple Checks

For more granular control, you can use both liveness and readiness checks:

```yaml
version: '3.8'

services:
  laravel-app:
    image: laravel-app:latest
    deploy:
      replicas: 3
      labels:
        # Labels for external health monitoring
        - "healthcheck.live=/health/live"
        - "healthcheck.ready=/health/ready"
    healthcheck:
      # Uses readiness endpoint for comprehensive checks
      test: ["CMD-SHELL", "curl -f http://localhost/health/ready || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    networks:
      - app-network
    volumes:
      - storage-data:/app/storage

  # Load balancer with health-aware routing
  traefik:
    image: traefik:v2.10
    command:
      - "--providers.docker.swarmMode=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
    ports:
      - "80:80"
    volumes:
      - "/var/run/docker.sock:/var/run/docker.sock:ro"
    networks:
      - app-network
    deploy:
      placement:
        constraints:
          - node.role == manager

volumes:
  storage-data:

networks:
  app-network:
    driver: overlay
```

### Health Check Best Practices for Docker Swarm

**1. Start Period**: Set `start_period` to allow your Laravel app time to boot:
```yaml
healthcheck:
  start_period: 40s  # Adjust based on your app's startup time
```

**2. Interval and Timeout**: Balance between responsiveness and load:
```yaml
healthcheck:
  interval: 30s   # Check every 30 seconds
  timeout: 10s    # Wait up to 10 seconds for response
  retries: 3      # Fail after 3 consecutive failures
```

**3. Using wget Instead of curl**:
```yaml
healthcheck:
  test: ["CMD", "wget", "--no-verbose", "--tries=1", "--spider", "http://localhost/health/ready"]
```

**4. Custom Script for Complex Checks**:
```yaml
healthcheck:
  test: ["CMD", "/app/scripts/health-check.sh"]
  interval: 30s
  timeout: 10s
  retries: 3
```

Create `/app/scripts/health-check.sh`:
```bash
#!/bin/bash
# Check liveness first (fast)
curl -f http://localhost/health/live || exit 1

# Then check readiness (comprehensive)
curl -f http://localhost/health/ready || exit 1
```

### Deployment Commands

Deploy your stack:
```bash
docker stack deploy -c docker-compose.yml laravel-app
```

Check service health:
```bash
# View service status
docker service ls

# Inspect service health
docker service ps laravel-app_laravel-app

# View service logs
docker service logs laravel-app_laravel-app
```

Monitor health status:
```bash
# Watch for unhealthy services
docker service ps --filter "desired-state=running" laravel-app_laravel-app

# Get detailed task information
docker inspect $(docker service ps -q laravel-app_laravel-app)
```

### Rolling Updates with Health Checks

Docker Swarm will automatically use health checks during rolling updates:

```yaml
services:
  laravel-app:
    deploy:
      update_config:
        parallelism: 2        # Update 2 containers at a time
        delay: 10s            # Wait 10s between batches
        failure_action: rollback
        order: start-first    # Start new container before stopping old
      rollback_config:
        parallelism: 1
        delay: 5s
```

Health checks ensure new containers are healthy before the old ones are removed.

## Testing

Run the package tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
