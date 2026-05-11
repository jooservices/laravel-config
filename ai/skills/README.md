# AI Skills Map

This repository keeps one set of contributor rules and exposes them through repository skills plus environment-specific guidance files.

See also:

- [AI Skills Usage Guide](./USAGE.md)

## Canonical repository skills

- `.github/skills/repo-quality-foundation/SKILL.md`
- `.github/skills/php-package-development/SKILL.md`
- `.github/skills/review-and-risk-assessment/SKILL.md`
- `.github/skills/documentation-sync/SKILL.md`
- `.github/skills/ci-hooks-maintenance/SKILL.md`

## Adapter layers

- `AGENTS.md`
- `CLAUDE.md`

## Intent

All adapters should reflect the same repository truth:

- package purpose and supported runtime behavior
- the `JOOservices\LaravelConfig\` namespace rule
- MongoDB-backed integration testing requirements
- no internal mocking for package persistence behavior
- Pint over conflicting style guidance
- Git branch and PR workflow
- when agents must stop and ask
