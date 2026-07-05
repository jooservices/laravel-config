# CI/CD

## CI workflow

The CI workflow runs on pushes to `develop` and `master`, plus pull requests targeting those branches.

It performs:

- Composer validation and locked-state audit
- lint matrix for Pint, PHPCS, PHPStan (max + strict rules), PHPMD, PHP-CS-Fixer, and AI instruction sync
- dependency review on pull requests
- MongoDB-backed Laravel 12 and 13 test matrix
- coverage generation with a **95%** threshold gate
- optional coverage upload when `CODECOV_TOKEN` is available

## Release workflow

Tag pushes matching `v*.*.*` validate the package, create a GitHub release, and publish to Packagist when credentials are configured.

Prerelease tags containing `-` do not create a release or publish.

## Secret scanning

TruffleHog OSS runs on pushes, pull requests, schedule, and manual dispatch.

## Branch protection

See [Branch protection](./07-branch-protection.md). `develop` and `master` require the CI status checks listed there.

## Scorecard

The OpenSSF Scorecard workflow runs on `master`, on schedule, and on manual dispatch.

## Local parity

```bash
composer ci
```
