# Project Overview

`jooservices/laravel-config` is a Laravel package for MongoDB-backed runtime configuration.

## Main components

- `ConfigServiceProvider`: package registration, publishable config, and Artisan commands
- `Facades\Config`: convenience access to the service binding
- `Models\Config`: MongoDB document model for stored config entries
- `Services\ConfigService`: path parsing, typed normalization, MongoDB persistence, cache coordination, and read APIs
- `Support\ConfigPath`: validates and splits `group.key` paths

## Runtime shape

- MongoDB stores the source of truth
- an optional cache stores the full normalized config map
- each PHP process keeps an in-memory copy after first load
- writes update MongoDB and keep the cached map coherent
