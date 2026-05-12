---
name: release-management
description: "Use when preparing, validating, tagging, or publishing jooservices/laravel-config releases."
---

# Release Management Skill

## Repository Truth

- Package: `jooservices/laravel-config`
- Versioning follows semantic versioning: `MAJOR.MINOR.PATCH`, tagged as `vX.Y.Z`.
- Normal feature, fix, docs, and chore work starts from `develop` and opens a PR back to `develop`.
- Releases start from latest `develop` on `release/<version>` and open a PR to `master`.
- Never commit directly to `master` or `develop`; all updates to those branches must go through pull requests.
- Stop and ask if branch state, version intent, changelog content, or release metadata is unclear.

## Version Decision

- Patch: backward-compatible bug fixes, documentation corrections, CI-only maintenance, dependency patch updates.
- Minor: backward-compatible features, new configuration options, supported Laravel integration improvements.
- Major: breaking API/config behavior, dropped PHP or Laravel support, renamed public classes, incompatible migration paths.

Do not widen PHP, Laravel, MongoDB, or Composer constraints without explicit approval.
Do not drop supported PHP or Laravel versions without explicit approval.

## Preflight

1. Inspect tags and releases:
   - `git tag --sort=-version:refname`
   - `gh release list --repo jooservices/laravel-config`
2. Inspect current branches:
   - `git fetch --all --prune`
   - compare `master` and `develop`
3. Inspect release-facing files:
   - `CHANGELOG.md`
   - `README.md`
   - `composer.json`
   - `composer.lock`
   - `.github/workflows/release.yml`
4. Validate package state:
   - `composer validate --strict`
   - `composer lint:all`
   - `composer test`

## Release Flow

1. Checkout latest `develop`.
2. Create `release/<version>` from `develop`.
3. Update `CHANGELOG.md` and any release metadata that exists in this repo.
4. Run validation locally.
5. Open PR from `release/<version>` to `master`.
6. Merge only after required checks pass, required reviews are approved, no requested changes remain, no unresolved review threads remain, and the branch is mergeable.
7. Tag from latest `master` with `vX.Y.Z`.
8. Create or verify the GitHub release.
9. Merge `master` back into `develop` after the release through a pull request and normal review/check gates.
10. Delete only safely merged release branches; never delete `master`, `develop`, or unmerged work.

## Failure Rules

- If CI fails, inspect logs before changing release scope.
- If Packagist or GitHub release publishing fails, fix automation/secrets separately and rerun the release job.
- If local and remote branch state diverges unexpectedly, stop and report.
