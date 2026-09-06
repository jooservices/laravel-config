# Installation

## Requirements

- PHP 8.5+
- Laravel 12 or 13
- `mongodb/laravel-mongodb` `^5.7`
- MongoDB PHP extension

## Install the package

Until `v4.0.0` is tagged, install from VCS / path. After release:

```bash
composer require jooservices/laravel-config:^4.0
```

## Publish the package config

```bash
php artisan vendor:publish --tag=config-store-config
```

## Configure MongoDB

The package uses the `mongodb` connection and the `configs` collection. Set the connection DSN and database in your Laravel environment.

## Ensure the unique `(group, key)` index

```bash
php artisan config-store:ensure-index
```

This command ensures the required unique compound index on `group` + `key`.

See [UPGRADE-4.0.md](../../UPGRADE-4.0.md) for breaking changes from the 1.x line.
