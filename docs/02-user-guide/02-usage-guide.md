# Usage Guide

## Read APIs

Any `path` argument must use `group.key`; `group(group)` expects only the group name.

- `get(path, default = null)`: read from memory or cache-backed map
- `has(path)`: check existence, including stored `null` values
- `group(group)`: return one group as an associative array
- `all()`: return the full normalized config map
- `fresh(path, default = null)`: read directly from MongoDB without updating the in-memory map

## Write APIs

- `set(path, value, type = null)`: persist and normalize a value
- `forget(path)`: remove one config entry
- `refresh()`: clear memory and cache, then reload from MongoDB

## Cache behavior

- first reads load the full map
- writes keep the shared cache coherent
- process-local memory is still stale across multiple workers until `refresh()` or process recycle

## Sensitive values

This package does not encrypt values by default. Keep secrets out of the config store unless your operational controls make that acceptable.
