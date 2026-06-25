# Changelog

All notable changes to `jooservices/laravel-config` should be documented here.

## Unreleased

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
