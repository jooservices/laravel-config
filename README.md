# jooservices/laravel-config

Store and retrieve application configuration in MongoDB with in-memory and cache support.

## Purpose

This package persists configuration as **group / key / value / type** in MongoDB and exposes a simple API. Values are loaded once (from cache or database), kept in memory, and cache is updated on `set()` and `forget()`.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- MongoDB (via [mongodb/laravel-mongodb](https://github.com/mongodb/laravel-mongodb))
- MongoDB PHP extension

## Installation

```bash
composer require jooservices/laravel-config
```

Publish config:

```bash
php artisan vendor:publish --tag=config-store-config
```

Configure your MongoDB connection in `config/database.php` (see [Laravel MongoDB docs](https://github.com/mongodb/laravel-mongodb#configuration)). The package uses the `mongodb` connection and the `configs` collection; the database name is taken from that connection (e.g. `MONGODB_DATABASE`).

## Configuration

Publish the config file to `config/config-store.php`:

| Option | Default | Description |
|--------|---------|-------------|
| `cache_enabled` | `true` | Use cache for the full config map |
| `cache_store` | default | Cache store name (null = default) |
| `cache_ttl` | `3600` | TTL in seconds |
| `cache_key` | `jooservices_config_all` | Cache key for the map |

Cache uses Laravel’s cache API: **no specific driver is required**. If `cache_store` is not set, the app’s default cache driver is used (e.g. `file`, `redis`, `database`, `array`). Set `cache_store` to use a specific store (e.g. `redis`).

Env examples:

- `CONFIG_STORE_CACHE_ENABLED`
- `CONFIG_STORE_CACHE_STORE`
- `CONFIG_STORE_CACHE_TTL`
- `CONFIG_STORE_CACHE_KEY`

## Mongo index

The `configs` collection is created automatically on first write. Create a **unique compound index** on `group` + `key` so each group/key pair is unique (e.g. in MongoDB shell or your admin tool):

```javascript
db.configs.createIndex(
  { "group": 1, "key": 1 },
  { "unique": true }
);
```

## Usage

Use the `Config` facade (namespace `JooServices\LaravelConfig\Facades\Config`). Path format is **group.key** (e.g. `system.site_name`).

```php
use JooServices\LaravelConfig\Facades\Config;

// Get (from memory/cache/DB)
Config::get('system.site_name');              // null if missing
Config::get('system.site_name', 'Default');   // with default

// Set (persists and updates cache)
Config::set('system.site_name', 'XCrawler');
Config::set('payment.retry_times', 3);
Config::set('payment.rate', 1.5);
Config::set('system.enabled', true);
Config::set('system.items', ['a' => 1]);
Config::set('system.payload', '{"foo":"bar"}', 'json');

// Check
Config::has('system.site_name');

// Remove
Config::forget('system.site_name');

// By group
Config::group('system');   // ['site_name' => '...', 'enabled' => true, ...]

// Full map
Config::all();             // ['system' => [...], 'payment' => [...]]

// Reload from storage and cache
Config::refresh();

// Single key from DB (bypass in-memory/cache)
Config::fresh('system.site_name');
Config::fresh('system.missing', 'Default');
```

To avoid collision with Laravel’s `Config`:

```php
use JooServices\LaravelConfig\Models\Config as ConfigModel;
use JooServices\LaravelConfig\Facades\Config as ConfigStore;
```

## Cache behavior

- **First access** (`get`, `has`, `group`, `all`): load from cache; on miss, load from MongoDB, build the map, store in cache, then serve from memory.
- **Later accesses**: served from in-memory map only (no DB/cache reads).
- **set()** and **forget()**: update MongoDB and refresh the cached map and in-memory state.
- **refresh()**: clear in-memory and cache, then reload from MongoDB and repopulate cache (if enabled).

## Supported value types

Stored and normalized on load:

- `string`
- `int`
- `float`
- `bool`
- `array`
- `json` (stored as string, returned as array)
- `null`

Type can be set explicitly: `Config::set('app.count', '42', 'int')`. If omitted, it is inferred from the value.

## Testing

Tests use PHPUnit and Orchestra Testbench. The config store uses **MongoDB** (same as production). The test suite expects a MongoDB instance (e.g. local or in CI).

1. Configure MongoDB for tests (e.g. in `phpunit.xml` or `.env.testing`):

   - `MONGODB_URI` or `MONGO_URI` (e.g. `mongodb://localhost:27017`)
   - `MONGODB_DATABASE` (e.g. `jooservices_configs_test`)

2. Run tests:

```bash
composer test
```

Coverage:

```bash
composer test:coverage
```

For the Laravel app under Testbench, `phpunit.xml` can also define a MySQL connection (e.g. `DB_CONNECTION=mysql`, `DB_DATABASE=jooservices_configs`) if your tests need it; the package’s config store still uses MongoDB.

## Linting

Run all lint checks (Pint, PHPStan, PHPMD, PHPCS). Pint wins on formatting over PHPCS.

```bash
composer lint
```

Fix code style (Pint):

```bash
composer lint:fix
```

Run a specific linter:

```bash
composer lint:pint
composer lint:phpstan
composer lint:phpmd
composer lint:phpcs
```

Full check (lint + tests): `composer lint && composer test`

## License

MIT.

## Author

Viet Vu – jooservices@gmail.com
