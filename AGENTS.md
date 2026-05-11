# JOOservices Laravel Config Repository Instructions

This repository is a Laravel package named `jooservices/laravel-config`.

## Core intent

- preserve a small package surface focused on MongoDB-backed runtime configuration
- prefer minimal changes that fit the current service-provider, facade, model, and service layout
- treat tests and docs as part of the implementation
- stop and ask if requirements are unclear, conflicting, risky, or unsupported by the real code

## Package rules

- the canonical namespace is `JOOservices\LaravelConfig\`
- target PHP 8.5+ and Laravel 11 or 12
- keep package internals simple and avoid unnecessary abstractions
- use real integration-style tests for persistence behavior
- do not mock internal package behavior such as cache or Mongo persistence flows
- use mocks or fakes only for true external boundaries when required

## Quality rules

- formatting authority: `Pint`
- structural checks: `PHPCS`
- static analysis: `PHPStan`
- maintainability checks: `PHPMD`
- tests: `PHPUnit`

## Required commands

- `composer lint`
- `composer lint:all`
- `composer lint:fix`
- `composer test`
- `composer test:coverage`
- `composer check`
- `composer ci`

## MongoDB and runtime notes

- MongoDB must be available for integration tests and CI
- config paths must use `group.key`
- the in-memory map is process-local and may become stale across multiple workers or processes
- `fresh()` is a direct MongoDB read and does not refresh the in-memory map
- ensure the `group` + `key` unique compound index exists

## Git workflow

- `master` is the release branch
- `develop` is the integration branch
- normal work branches from the latest `develop`
- do not commit directly to `master` or `develop`
- do not delete unmerged branches silently
- if open PR review state or checks cannot be verified, stop and report that limitation

## Read these skills for non-trivial work

- `.github/skills/repo-quality-foundation/SKILL.md`
- `.github/skills/php-package-development/SKILL.md`
- `.github/skills/review-and-risk-assessment/SKILL.md`
- `.github/skills/documentation-sync/SKILL.md`
- `.github/skills/ci-hooks-maintenance/SKILL.md`
