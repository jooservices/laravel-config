# Repository audit against jooservices/dto

This audit compares `jooservices/laravel-config` with the local
`jooservices/dto` baseline at commit `98f0c04`, and with the recently adopted
`jooservices/laravel-events` Laravel/Mongo package pattern. DTO is a maturity
baseline only. Domain-specific DTO features are not copied into this package.

## Already consistent

- Package metadata, MIT license, Composer library type, support links.
- PHP 8.5 baseline and Laravel package auto-discovery.
- Composer scripts for `test`, `test:coverage`, `lint`, `lint:fix`, `check`,
  and `ci`.
- CaptainHook install hooks via Composer.
- Pint, PHPCS, PHPStan, and PHPMD present; Pint is the primary formatter.
- CI for `master` / `develop`: validate, lint, security, MongoDB-backed tests,
  Coverage upload as the final gate, release automation, scorecard, PR labeling,
  semantic PR, commitlint.
- Root `AGENTS.md`, `.editorconfig`, `.gitattributes`, `.gitleaks.toml`,
  `README.md`, `CHANGELOG.md`, and `LICENSE`.

## Intentionally different

- This package depends on Laravel and `mongodb/laravel-mongodb`; DTO is a
  framework-independent PHP library.
- This package uses Orchestra Testbench and a MongoDB service in CI; DTO does not.
- Docs describe typed MongoDB config storage, cache maps, and Artisan operators —
  not DTO hydration or schema generation.
- Next major is planned as **v4.0.0** (skip from 1.x) to match other JOOservices
  Laravel package rebuilds.

## Baseline gaps before this adopt

- Composer still allowed Laravel 11 and older `mongodb/laravel-mongodb` floors
  than the JOOservices v4 target (`^5.7`).
- Nested path semantics, encrypted type, bulk `setMany`, import `--dry-run` /
  `--force`, and redacted `ConfigChanged` payloads were not yet documented as
  the v4 contract.
- `SECURITY.md` / `CONTRIBUTING.md` were thinner than sibling Laravel packages.
- Leftover `laravel-events` strings in a few copied tooling/docs files.
- No `UPGRADE-4.0.md` or dto-comparison maintenance note for the adopt pass.
- AI instruction sync tooling still referenced in older changelog / scripts
  (planned drop for v4).

## Implemented in this adopt / v4 prep (docs)

- Align root governance docs with JOOservices brevity (`SECURITY`,
  `CONTRIBUTING`) and fix package-name leftovers in tooling metadata.
- Document v4 requirements and breaking behavior in `UPGRADE-4.0.md`,
  `[Unreleased]` / `4.0.0` CHANGELOG, and README (Packagist still 1.4.0).
- Record remaining operational risks after v4 prep.
- Register the package in the workspace `PROJECTS.md` registry.

## Deferred (code / release)

- Implement and land the PHP breaking changes listed in `UPGRADE-4.0.md`
  (encrypted type, nested paths, `setMany`, import flags, event redaction,
  Composer author, Larastan/Pint `per`, drop Laravel 11, drop instruction sync).
- Tag / Packagist publish of **v4.0.0** only after explicit version confirmation.
- Codecov / Sonar badges only after integrations are confirmed; keep optional
  secret-dependent steps non-blocking when secrets are absent.
