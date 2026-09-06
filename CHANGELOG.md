# Changelog

All notable changes to `jooservices/laravel-config` should be documented here.

## [Unreleased] — 4.0.0 (planned, not released)

JOOservices adopt + v4.0.0 preparation. **Not tagged.** Packagist remains at
`1.4.0` until release is confirmed.

### Breaking (planned)

- Require PHP `^8.5`, Laravel `^12|^13` only (drop Laravel 11), and
  `mongodb/laravel-mongodb` `^5.7`
- Nested paths: first segment = group, remainder = key (dots allowed in key)
- `ConfigType::Encrypted` (Laravel Crypt) for secrets
- `listOrdered()` returns normalized values (same as `get`)
- `ConfigChanged` no longer embeds plaintext for encrypted values
- Prefer package `Config` / `ConfigStore` facade — do not bind as Laravel `Config`
- Composer author `Viet Vu <jooservices@gmail.com>`
- Drop AI instruction sync tooling

### Added / changed (planned)

- `setMany()` / bulk import with a single cache bump
- `forgetMany()` / `clear()` with a single cache bump (import `--force` replace)
- Typed JSON export (`__type` / `__value`); CLI `--reveal-secrets` for get/list/export
- Import CLI `--dry-run`; `--force` required to replace without merge
- Doctor check for encrypted-value decryptability
- `getFloat()` accepts stored integers
- Fake store aligns typed getters + events with the real store
- Pint `per`, Larastan, and JOOservices CI / docs / governance parity with
  `dto` / `laravel-events`

### Docs

- Add `UPGRADE-4.0.md`, repo audit vs dto, and refreshed maintenance risks

## 1.3.0 - 2026-07-06

- add version-stamped cache coherence, `ConfigType` validation, JSON error hardening, typed getters, `Config::fake()`, and list/doctor/import/export commands
- add `ConfigStore` contract, `ConfigChanged` / `ConfigForgotten` events, and `remember()` helper
- align CI with jooservices/dto: PHPStan max + strict rules, coverage gate, SHA-pinned actions, dependency review, TruffleHog scanning
- add captainhook, `.editorconfig`, `.gitattributes`, `.gitleaks.toml`, and AI instruction sync verification
- document Laravel 11 as constraint-compatible but not CI-verified; CI matrix covers Laravel 12 and 13
- add import support for typed entries via `{"__type": "...", "__value": ...}` metadata objects
- raise CI coverage gate to 95% with expanded integration tests for commands, cache coherence, and edge cases
- add `composer bench` / `composer bench:quick` phpbench harness for config type parsing benchmarks

## 1.2.0 - 2026-06-25

- add Laravel 13 support alongside Laravel 11 and 12: `illuminate/support`, `illuminate/cache`, `illuminate/config`, and `illuminate/database` now accept `^11.0|^12.0|^13.0`
- add `orchestra/testbench:^11.0` to `require-dev` and a CI matrix testing Laravel 11, 12, and 13 against a real MongoDB service
- fix a PHPStan type ambiguity in `ConfigService::ensureIndexes()` surfaced by the dependency upgrade (the MongoDB Eloquent query builder's `raw()` was being resolved against the base Eloquent builder's signature)
- fix the release workflow's validate job hitting a coverage-driver warning that aborted release test runs before any test executed
- update docs and AI/agent guidance to state the Laravel 11/12/13 support range

## 1.1.0 - 2026-05-11

- BREAKING: standardize package namespace to `JOOservices\LaravelConfig\`
- add config path parsing, command-line helpers, and index management command
- fix cache consistency issues around cold writes and null handling
- add repository docs, workflows, and AI contributor guidance
