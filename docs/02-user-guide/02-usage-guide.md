# Usage Guide

## Read APIs

Any `path` argument must use `group.key…` (first segment = group).

- `get(path, default = null)`: read from memory or cache-backed map
- `getString`, `getInt`, `getFloat`, `getBool`, `getArray`: typed reads that throw when a stored value exists but does not match the requested type (`getFloat` accepts int or float)
- `has(path)`: check existence, including stored `null` values
- `group(group)`: return one group as an associative array
- `all()`: return the full normalized config map
- `listOrdered()` / `listPaths()`: ordered inventory with types (or paths only)
- `fresh(path, default = null)`: read directly from MongoDB without updating the in-memory map
- `remember(path, default, type = null)`: return an existing value or persist the default and return it

## Write APIs

- `set(path, value, type = null)`: persist and normalize a value; unknown `type` strings and malformed JSON throw
- `setMany(entries)`: persist many paths with a **single** cache version bump
- `forget(path)` / `forgetMany(paths)` / `clear()`: remove one, many, or all paths (`forgetMany` / `clear` bump once)
- `refresh()`: clear memory and cache, then reload from MongoDB

## Cache behavior

- first reads load the full map from cache or MongoDB
- the shared cache stores an **encrypted** full-map payload plus a version stamp
- writes update MongoDB, bump the shared cache version stamp, and rewrite the cached full map in the current process
- loaded processes reload automatically when they detect a newer cache version stamp
- stored `null` is returned as `null`; caller defaults are not applied when the key exists with a null value
- bool normalization uses PHP `filter_var(..., FILTER_VALIDATE_BOOLEAN)` semantics

## Multi-process limitations

- in-memory state remains process-local until `refresh()` or process recycle
- each mutation rewrites the full cached map; keep collections config-sized
- prefer `setMany` / bulk import so the stamp bumps once per batch
- use a dedicated cache store/prefix in production and treat the shared cache as a trusted boundary

## Sensitive values

Use `ConfigType::Encrypted` (`encrypted`) for secrets. Laravel Crypt encrypts at
rest. `ConfigChanged` events redact encrypted payloads. CLI `get` / `list` /
`export` redact by default; pass `--reveal-secrets` when operators need plaintext
(export omits encrypted rows unless that flag is set).

## Consumer testing

Use `Config::fake()` in application tests to swap in an in-memory store without MongoDB.
Typed getters and change events match the real store:

```php
Config::fake([
    'system' => ['site_name' => 'XCrawler'],
]);
```
