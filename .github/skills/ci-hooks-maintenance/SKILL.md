# CI Hooks Maintenance

## Purpose

Use this skill when changing GitHub Actions or release automation.

## Rules

- CI must run on `develop`, `master`, and pull requests
- MongoDB must be available for the test job
- avoid secret-dependent failures; optional integrations must be conditional
- release automation must validate package quality before publishing
- scorecard automation should be read-only except for SARIF upload permissions
