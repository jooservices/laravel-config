# Repository audit against jooservices/dto

This audit compares `jooservices/laravel-config` with the local
`jooservices/dto` baseline, and with the adopted `jooservices/laravel-events`
Laravel/Mongo package pattern. DTO is a maturity baseline only. Domain-specific
DTO features are not copied into this package.

## Already consistent

- Package metadata, MIT license, Composer library type, support links.
- PHP 8.5 baseline and Laravel package auto-discovery.
- Composer scripts for `test`, `test:coverage`, `lint`, `lint:fix`, `check`,
  and `ci`.
- CaptainHook install hooks via Composer.
- Pint, PHPCS, PHPStan, and PHPMD present; Pint is the primary formatter (`per`).
- CI for `master` / `develop`: validate, lint, security, MongoDB-backed tests,
  Coverage upload as the final gate, release automation, scorecard, PR labeling,
  semantic PR, commitlint.
- Root `AGENTS.md`, `.editorconfig`, `.gitattributes`, `.gitleaks.toml`,
  `README.md`, `CHANGELOG.md`, and `LICENSE`.
- v4 runtime: nested paths, `encrypted`, `setMany` / `forgetMany` / `clear`,
  typed export/import, CLI secret redaction, Larastan.

## Intentionally different

- This package depends on Laravel and `mongodb/laravel-mongodb`; DTO is a
  framework-independent PHP library.
- This package uses Orchestra Testbench and a MongoDB service in CI; DTO does not.
- Docs describe typed MongoDB config storage, cache maps, and Artisan operators —
  not DTO hydration or schema generation.
- Next major is planned as **v4.0.0** (skip from 1.x) to match other JOOservices
  Laravel package rebuilds.

## Remaining release work

- Tag / Packagist publish of **v4.0.0** only after explicit version confirmation.
- Codecov / Sonar badges only after integrations are confirmed; keep optional
  secret-dependent steps non-blocking when secrets are absent.
