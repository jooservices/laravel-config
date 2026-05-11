# Risks, Legacy, And Gaps

## Current operational risks

- the in-memory config map is process-local and can become stale across workers or long-lived PHP processes
- stored values are not encrypted by default
- the package caches the full config map, so very large config sets increase reload and refresh costs

## Current boundaries

- `fresh()` is intentionally a direct MongoDB read and does not refresh the in-memory map
- the package assumes a unique `group` + `key` MongoDB index exists
- there is no distributed invalidation strategy beyond explicit refresh and cache invalidation

## Upgrade impact

- the canonical namespace is now `JOOservices\LaravelConfig\`
- existing imports using `JooServices\LaravelConfig\` must be updated by consumers
