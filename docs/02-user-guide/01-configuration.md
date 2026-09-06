# Configuration

Published file: `config/config-store.php`

## Options

- `cache_enabled`: enable or disable the cached full config map
- `cache_store`: use a named Laravel cache store or the default store when null
- `cache_ttl`: cache lifetime in seconds
- `cache_key`: cache key for the stored full map (encrypted payload)
- `cache_version_key`: shared version stamp key (defaults to `{cache_key}:version`)

## Supported value types

- `string`
- `int`
- `float`
- `bool`
- `array`
- `json`: stored as a JSON string and normalized back to an array when read
- `null`
- `encrypted`: stored via Laravel Crypt; prefer for secrets

## Path validation

Paths use `group.key…`. The **first** segment is the group; the **remainder**
(joined with dots) is the key. Nested keys like `mail.smtp.host` are valid.

Invalid patterns include empty paths, missing first dot, leading dots, trailing
dots, double dots, empty groups, and empty keys.
