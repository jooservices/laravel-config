# Testing

The package test suite is integration-oriented.

## Rules

- use real MongoDB-backed persistence tests where practical
- do not mock internal package behavior such as `ConfigService`, the facade, or the MongoDB model flow
- use mocks or fakes only for true external boundaries if needed
- `Config::fake()` is provided for consumer applications; package tests still prefer real MongoDB integration coverage for persistence behavior

## Commands

```bash
composer test
composer test:coverage
composer ci
```

## Coverage gate

CI enforces a minimum **95%** statement coverage threshold from `build/coverage/clover.xml`.

## Consumer app testing

```php
use JOOservices\LaravelConfig\Facades\Config;

Config::fake([
    'system' => ['site_name' => 'XCrawler'],
]);

$this->assertSame('XCrawler', Config::get('system.site_name'));
```

Reset by resolving a fresh application instance or calling `Config::swap()` with the real binding in your test harness.
