#!/usr/bin/env bash
set -euo pipefail

REPO="${1:-jooservices/laravel-config}"

protect_branch() {
  local branch="$1"

  gh api \
    --method PUT \
    -H "Accept: application/vnd.github+json" \
    "/repos/${REPO}/branches/${branch}/protection" \
    --input - <<EOF
{
  "required_status_checks": {
    "strict": true,
    "checks": [
      {"context": "Security Checks"},
      {"context": "Lint - Pint"},
      {"context": "Lint - PHPCS"},
      {"context": "Lint - PHPStan"},
      {"context": "Lint - PHPMD"},
      {"context": "Lint - PHP-CS-Fixer"},
      {"context": "Lint - AI Instructions"},
      {"context": "Tests & Coverage (Laravel 12)"},
      {"context": "Tests & Coverage (Laravel 13)"},
      {"context": "Tests & Coverage"}
    ]
  },
  "enforce_admins": false,
  "required_pull_request_reviews": {
    "required_approving_review_count": 0,
    "dismiss_stale_reviews": true
  },
  "restrictions": null,
  "required_linear_history": false,
  "allow_force_pushes": false,
  "allow_deletions": false
}
EOF
}

for branch in develop master; do
  echo "Protecting ${REPO}:${branch}"
  protect_branch "${branch}"
done

echo "Branch protection applied."
