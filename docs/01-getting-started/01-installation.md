# Installation

## Requirements

- PHP 8.5+
- Laravel 11 or 12
- `mongodb/laravel-mongodb`
- MongoDB PHP extension

## Install the package

```bash
composer require jooservices/laravel-config
```

## Publish the package config

```bash
php artisan vendor:publish --tag=config-store-config
```

## Configure MongoDB

The package uses the `mongodb` connection and the `configs` collection. Set the connection DSN and database in your Laravel environment.

## Ensure the unique index

```bash
php artisan config-store:ensure-index
```
