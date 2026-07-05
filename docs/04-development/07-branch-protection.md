# Branch protection

`develop` and `master` must exist and stay protected like jooservices/dto.

Apply or verify protection with GitHub CLI (requires admin access):

```bash
./tools/configure-branch-protection.sh
```

Protected branches should require:

- pull request before merge
- status checks: `Security Checks`, `Lint - Pint`, `Lint - PHPCS`, `Lint - PHPStan`, `Lint - PHPMD`, `Lint - PHP-CS-Fixer`, `Lint - AI Instructions`, `Tests & Coverage (Laravel 12)`, `Tests & Coverage (Laravel 13)`, and aggregate `Tests & Coverage`
- stale review dismissal (optional)
- no direct pushes except administrators (optional)

Default branch: `develop`.
