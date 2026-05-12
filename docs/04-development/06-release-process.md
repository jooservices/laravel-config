# Release Process

The repository's release automation is defined in `.github/workflows/release.yml`.

## Branch policy

- normal feature, fix, docs, and chore work starts from latest `develop`
- release work starts from latest `develop` on `release/<version>`
- release pull requests target `master`
- after the release is merged into `master`, back-merge `master` into `develop` through a normal pull request

## Trigger

Push a semantic version tag matching:

```text
v*.*.*
```

Example:

```bash
git tag v1.0.0
git push origin v1.0.0
```

## Workflow stages

### 1. Validate release

The workflow provisions MongoDB, then runs:

- `composer validate --strict`
- `composer audit --no-dev`
- `composer check`

### 2. Create GitHub release

The workflow generates GitHub release notes from the pushed tag.

### 3. Publish to Packagist

When `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN` are configured, the workflow triggers a Packagist refresh for `jooservices/laravel-config`.

## Practical maintainer checklist

Before tagging:

- confirm the release content has already merged to `master` through a reviewed pull request
- confirm `master` and `develop` are synchronized according to the approved release flow
- confirm `composer lint:all` and `composer test` pass locally
- update release-facing docs or changelog entries when behavior, workflow, or support expectations changed

If the release source branch, compatibility impact, or release notes scope is unclear, stop and ask instead of guessing.
