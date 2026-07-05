# Usage Guide

## Read APIs

Any `path` argument must use `group.key`; `group(group)` expects only the group name.

- `get(path, default = null)`: read from memory or cache-backed map
- `getString`, `getInt`, `getFloat`, `getBool`, `getArray`: typed reads that throw when a stored value exists but does not match the requested type
- `has(path)`: check existence, including stored `null` values
- `group(group)`: return one group as an associative array
- `all()`: return the full normalized config map
- `fresh(path, default = null)`: read directly from MongoDB without updating the in-memory map
- `remember(path, default, type = null)`: return an existing value or persist the default and return it

## Write APIs

- `set(path, value, type = null)`: persist and normalize a value; unknown `type` strings and malformed JSON now throw
- `forget(path)`: remove one config entry
- `refresh()`: clear memory and cache, then reload from MongoDB

## Cache behavior

- first reads load the full map from cache or MongoDB
- writes update MongoDB, bump a shared cache version stamp, and rewrite the cached full map in the current process
- loaded processes reload automatically when they detect a newer cache version stamp
- stored `null` is returned as `null`; caller defaults are not applied when the key exists with a null value
- bool normalization uses PHP `filter_var(..., FILTER_VALIDATE_BOOLEAN)` semantics

## Multi-process limitations

- in-memory state remains process-local until `refresh()` or process recycle
- each mutation rewrites the full cached map; keep collections config-sized
- use a dedicated cache store/prefix in production and treat the shared cache as a trusted boundary

## Sensitive values

This package does not encrypt values by default. Keep secrets out of the config store unless your operational controls make that acceptable.

## Consumer testing

Use `Config::fake()` in application tests to swap in an in-memory store without MongoDB:

```php
Config::fake([
    'system' => ['site_name' => 'XCrawler'],
]);
```
