# AI Skills Usage Guide

## Start here

For any non-trivial task, agents should read:

- `AGENTS.md`
- `CLAUDE.md`
- `ai/skills/README.md`

The canonical skill source lives in `.github/skills/` on GitHub.

## Recommended workflow

1. Read `AGENTS.md`.
2. Load the repository skill files relevant to the task.
3. Verify package behavior in real code before making assumptions.
4. Implement with tests and docs in the same change.
5. Run the required validation commands before finishing.

## Use these skills when

- changing package code: `repo-quality-foundation`, `php-package-development`
- auditing or reviewing risk: `review-and-risk-assessment`
- updating docs: `documentation-sync`
- changing CI, hooks, or release automation: `ci-hooks-maintenance`
