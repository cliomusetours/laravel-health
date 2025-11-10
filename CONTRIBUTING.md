# Contributing

Contributions are welcome and will be fully credited! We accept contributions via Pull Requests on [GitHub](https://github.com/cliomusetours/laravel-health).

## Pull Requests

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your main branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Testing

We use [Pest PHP](https://pestphp.com/) for testing. Please write tests for new features and ensure all tests pass before submitting a pull request:

```bash
composer test
```

## Coding Standards

We follow the PSR-12 coding standard. Please ensure your code adheres to these standards. You can use PHP CS Fixer to automatically fix coding standard issues:

```bash
composer format
```

## Adding New Health Checks

If you're contributing a new built-in health check:

1. Create the check class in `src/Checks/`
2. Implement the `HealthCheck` contract
3. Add configuration defaults to `config/health.php`
4. Write unit tests in `tests/Unit/`
5. Document the check in `README.md`
6. Update `CHANGELOG.md`

Example structure:

```php
<?php

namespace Cliomusetours\LaravelHealth\Checks;

use Cliomusetours\LaravelHealth\Contracts\HealthCheck;

class MyNewCheck implements HealthCheck
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('health.checks.' . self::class, []);
    }

    public function name(): string
    {
        return 'my_new_check';
    }

    public function run(): array
    {
        $startTime = microtime(true);

        try {
            // Your check logic here
            
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'ok',
                'duration_ms' => round($duration, 2),
                'message' => 'Check passed',
                'meta' => [],
            ];
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'critical',
                'duration_ms' => round($duration, 2),
                'message' => 'Check failed: ' . $e->getMessage(),
                'meta' => ['error' => get_class($e)],
            ];
        }
    }
}
```

## Reporting Issues

We use GitHub issues to track bugs and feature requests. Before submitting a new issue, please:

- Check if the issue has already been reported
- Provide a clear title and description
- Include as much relevant information as possible
- Include code samples demonstrating the issue if applicable

## Security Vulnerabilities

If you discover a security vulnerability, please send an email to security@cliomusetours.com. All security vulnerabilities will be promptly addressed.

**Happy coding!**
