# Configuration

Published file: `config/config-store.php`

## Options

- `cache_enabled`: enable or disable the cached full config map
- `cache_store`: use a named Laravel cache store or the default store when null
- `cache_ttl`: cache lifetime in seconds
- `cache_key`: cache key for the stored full map

## Supported value types

- `string`
- `int`
- `float`
- `bool`
- `array`
- `json`: stored as a JSON string and normalized back to an array when read
- `null`

## Path validation

All APIs expect `group.key` paths.

Invalid patterns include empty paths, missing dots, leading dots, trailing dots, double dots, empty groups, and empty keys.
