# Risks, Legacy, And Gaps

## Current operational risks

- the in-memory config map is process-local and can become stale across workers or long-lived PHP processes until `refresh()` or process recycle
- stored values are not encrypted by default
- the package caches the full config map, so very large config sets increase reload and refresh costs
- the shared cache is a trusted boundary; use a dedicated cache store/prefix in production

## Current boundaries

- `fresh()` is intentionally a direct MongoDB read and does not refresh the in-memory map
- the package assumes a unique `group` + `key` MongoDB index exists
- distributed invalidation is limited to cache version stamps plus explicit `refresh()`; there is no cross-app broadcast layer
- documents seeded outside the package with unknown `type` values are returned as raw stored values

## Upgrade impact

- the canonical namespace is now `JOOservices\LaravelConfig\`
- existing imports using `JooServices\LaravelConfig\` must be updated by consumers
- invalid `type` strings and malformed JSON inputs now throw instead of silently coercing values

## Laravel support matrix

- Laravel 12 and 13 are CI-verified
- Laravel 11 remains constraint-compatible but is not CI-verified because current testbench resolution conflicts with Composer audit policy
