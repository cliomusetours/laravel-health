# Changelog

All notable changes to `laravel-health` will be documented in this file.

## 1.0.0

### Added
- Initial release
- Liveness endpoint (`GET /health/live`) for fast availability checks
- Readiness endpoint (`GET /health/ready`) for comprehensive health checks
- Built-in health checks:
  - Database connectivity and query latency check
  - Cache availability check (Redis, Memcached, File drivers)
  - Queue worker status and backlog monitoring
  - Filesystem storage operations check
  - External HTTP service probe
  - Business logic check (customizable placeholder)
- Response caching with configurable TTL
- Per-check and global timeout handling
- Event dispatching (HealthCheckStarted, HealthCheckPassed, HealthCheckFailed)
- Artisan commands:
  - `health:run` - Run all health checks
  - `health:list` - List all configured checks
  - `health:check {check}` - Run a specific check
- Comprehensive test suite with Pest
- Support for Laravel 9, 10, and 11
- Support for PHP 8.1, 8.2, and 8.3
- GitHub Actions CI workflow
- Comprehensive documentation

### Features
- JSON-only responses
- HTTP 503 status on critical failures
- Configurable route prefix and middleware
- Easy to extend with custom health checks
- Docker Swarm ready liveness and readiness probes
