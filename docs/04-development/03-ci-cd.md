# CI/CD

## CI workflow

The CI workflow runs on pushes to `develop` and `master`, plus pull requests targeting those branches.

It performs:

- Composer validation and audit
- lint matrix for Pint, PHPCS, PHPStan, and PHPMD
- MongoDB-backed test execution with coverage output
- optional coverage upload when `CODECOV_TOKEN` is available

## Release workflow

Tag pushes matching `v*.*.*` validate the package, create a GitHub release, and optionally trigger Packagist when credentials are configured.

## Scorecard

The OpenSSF Scorecard workflow runs on `master`, on schedule, and on manual dispatch.
