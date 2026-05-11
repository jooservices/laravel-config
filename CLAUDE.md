# Claude Code Guidance

Use this repository as a Laravel package, not a full Laravel app.

## Non-negotiable rules

- namespace must be `JOOservices\LaravelConfig\`
- target PHP 8.5+ and Laravel 11 or 12
- Pint wins when style tools disagree
- do not add internal mocks for package persistence behavior
- stop and ask when repository truth is missing or conflicts with the request

## Primary commands

```bash
composer lint
composer lint:all
composer lint:fix
composer test
composer test:coverage
composer check
composer ci
```

## Implementation guidance

- keep commands thin and route behavior through `ConfigService`
- keep provider wiring and facade behavior minimal
- prefer value-object style parsing when it reduces ambiguity, such as config path handling
- document MongoDB index requirements and stale-memory limitations
- do not introduce distributed invalidation or encryption layers unless the real scope justifies them
