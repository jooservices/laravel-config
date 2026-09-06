# JOOservices Laravel Config

[![CI](https://github.com/jooservices/laravel-config/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/jooservices/laravel-config/actions/workflows/ci.yml)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/jooservices/laravel-config/badge)](https://securityscorecards.dev/viewer/?uri=github.com/jooservices/laravel-config)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-blue.svg)](https://www.php.net/)
[![Release](https://img.shields.io/badge/version-4.0.0-blue.svg)](CHANGELOG.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](./LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/jooservices/laravel-config)](https://packagist.org/packages/jooservices/laravel-config)

MongoDB-backed typed application configuration for Laravel, with optional cache
and Artisan operator commands.

Package: `jooservices/laravel-config`

> **`v4.0.0` includes breaking changes from `1.x`.** See [UPGRADE-4.0.md](./UPGRADE-4.0.md).

**Current release: `4.0.0`** · Laravel 12/13 · PHP 8.5+

## Install

```bash
composer require jooservices/laravel-config:^4.0
```

Publish configuration:

```bash
php artisan vendor:publish --tag=config-store-config
```

## Requirements

- PHP 8.5+
- Laravel 12 or 13 only (Laravel 11 dropped)
- MongoDB via `mongodb/laravel-mongodb` ^5.7
- MongoDB PHP extension

## What the package does

- stores values as `group`, `key`, `value`, and `type` documents in MongoDB
- loads a full in-memory map on first read and optionally caches that map
- typed normalization: `string`, `int`, `float`, `bool`, `array`, `json`, `null`,
  and `encrypted` (Laravel Crypt) for secrets
- nested paths: first segment = group, remainder = key (dots allowed in key),
  e.g. `mail.smtp.host`
- runtime `get`, typed getters, `set`, `setMany`, `forget`, `forgetMany`,
  `clear`, `remember`, `listOrdered` (normalized), `group`, `all`, `refresh`,
  and `fresh`
- Artisan commands including import `--dry-run` / `--force` and CLI
  `--reveal-secrets`
- `Config::fake()` for consumer-app tests without MongoDB

Prefer `JOOservices\LaravelConfig\Facades\Config` or the `ConfigStore` alias —
do not bind this package as Laravel’s native `Config`.

## Quick example

```php
use JOOservices\LaravelConfig\Facades\Config;

Config::set('system.site_name', 'XCrawler');
Config::set('system.enabled', true);
Config::set('payment.retry_times', 3);
Config::set('mail.smtp.host', 'smtp.example.com');

$siteName = Config::get('system.site_name');
$system = Config::group('system');
$fresh = Config::fresh('system.site_name');
```

## Path format

`group.key…` — first segment is the group; the rest (with dots) is the key.

- valid: `system.site_name`, `mail.smtp.host`
- invalid: `system`, `.system.site_name`, `system.`, `system..site_name`

## Cache and memory behavior

- `get`, `has`, `group`, and `all` load from memory first
- cold memory reads the cached full map, then MongoDB on miss
- mutations update MongoDB, bump a shared cache version stamp, and refresh the
  process map; `setMany` / bulk import bump once per batch
- `refresh` clears memory + cache key and reloads from MongoDB
- `fresh` bypasses memory and cache for a direct MongoDB read

Limitations: process-local memory can go stale in long-lived workers; treat the
shared cache as a trusted boundary; keep collections config-sized.

## MongoDB index

```bash
php artisan config-store:ensure-index
```

Unique compound index on `group` + `key`.

## Artisan commands

```bash
php artisan config-store:get system.site_name --default="Default"
php artisan config-store:get system.secret --reveal-secrets
php artisan config-store:set system.site_name XCrawler
php artisan config-store:set system.enabled true --type=bool
php artisan config-store:forget system.site_name
php artisan config-store:list system --json --with-types
php artisan config-store:doctor
php artisan config-store:export storage/config-store.json --reveal-secrets
php artisan config-store:import storage/config-store.json --dry-run
php artisan config-store:import storage/config-store.json --merge
php artisan config-store:import storage/config-store.json --force
php artisan config-store:refresh
php artisan config-store:ensure-index
```

Import: use `--dry-run` to preview. Replace without merge requires `--force`.
Export/list/get redact encrypted values unless `--reveal-secrets`.

## Security note

Use `ConfigType::Encrypted` for secrets. `ConfigChanged` does not embed
plaintext for encrypted values. Access control for MongoDB, backups, and the
cache store remain application responsibilities.

## Documentation

- [Documentation Hub](./docs/README.md)
- [Upgrade to 4.0](./UPGRADE-4.0.md)
- [Installation](./docs/01-getting-started/01-installation.md)
- [Quick Start](./docs/01-getting-started/02-quick-start.md)
- [Usage Guide](./docs/02-user-guide/02-usage-guide.md)
- [Risks, Legacy, and Gaps](./docs/05-maintenance/01-risks-legacy-and-gaps.md)
- [Repo audit vs dto](./docs/05-maintenance/03-repo-audit-dto-comparison.md)
- [Changelog](./CHANGELOG.md)

## Development

```bash
composer lint
composer lint:all
composer test
composer test:coverage
composer check
composer ci
```

MongoDB is required for integration tests.

## Community

- [Contributing](./CONTRIBUTING.md)
- [Security Policy](./SECURITY.md)
- [License](./LICENSE)
