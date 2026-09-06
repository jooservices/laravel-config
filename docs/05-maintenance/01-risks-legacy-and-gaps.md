# Risks, legacy, and gaps

Remaining operational risks after the JOOservices adopt / **v4.0.0** release.

## Operational risks

- **Process-local memory:** the in-memory config map is per PHP process. Long-lived
  workers can serve stale values until `refresh()`, a newer cache version stamp is
  observed, or the process recycles.
- **Cache trust:** the shared cache stores an **encrypted** full-map payload plus a
  version stamp. Use a dedicated cache store/prefix in production; do not share with
  untrusted writers. Process memory still holds decrypted values after load.
- **Full-map rewrites:** mutations rewrite the cached map. Keep collections
  config-sized (hundreds of keys, not thousands). Prefer `setMany` / `forgetMany` /
  bulk import so the stamp bumps once per batch.
- **Secrets:** `ConfigType::Encrypted` encrypts at rest via Laravel Crypt. Key
  rotation, MongoDB ACLs, backups, and who can call `get` / `--reveal-secrets`
  remain application responsibilities. `ConfigChanged` does not embed plaintext for
  encrypted values. CLI export omits encrypted rows unless `--reveal-secrets`.
- **Index assumption:** a unique compound index on `group` + `key` is required;
  run `config-store:ensure-index` in deploy.

## Boundaries

- `fresh()` reads MongoDB directly and does not refresh the in-memory map.
- Distributed invalidation is limited to cache version stamps plus explicit
  `refresh()` — no cross-app broadcast layer.
- Documents seeded outside the package with unknown `type` values are returned as
  raw stored values.
- This package is not a replacement for Laravel’s `config/` file tree.

## v4 migration risks

- Nested path semantics change how multi-dot keys are stored (first segment =
  group). Existing multi-dot keys need an explicit data migration if documents
  were stored under a different group/key split.
- Laravel 11 consumers must upgrade to Laravel 12 or 13.
- Consumers that bound the package as Laravel’s `Config` facade must switch to
  `JOOservices\LaravelConfig\Facades\Config` or `ConfigStore`.
- Install with `composer require jooservices/laravel-config:^4.0` after the
  Packagist publish completes for this tag.

## Tooling / CI gaps

- Codecov and Sonar stay optional until repository secrets/integrations are
  confirmed; avoid blocking badges that lie.
- AI instruction sync tooling has been removed; keep `AGENTS.md` and `.github`
  guidance as the source of truth.
