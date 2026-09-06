# Upgrade to v4.0.0

`v4.0.0` is a **breaking** major from the `1.x` line (JOOservices adopt + quality
floor). Do not tag or publish until the version is confirmed. Packagist remains
on `1.4.0` until then.

## Requirements

- PHP `^8.5`
- Laravel `^12.0|^13.0` only (Laravel 11 dropped)
- `mongodb/laravel-mongodb` `^5.7`
- Pint `per` preset; PHPStan with Larastan
- Composer author: `Viet Vu <jooservices@gmail.com>`

## Steps

```bash
composer require jooservices/laravel-config:^4.0
php artisan vendor:publish --tag=config-store-config --force   # if you customize config
php artisan config-store:ensure-index
```

## Breaking changes

### Nested paths

Paths remain `group.key…`. The **first** segment is the group; the **remainder**
(joined with dots) is the key. Dots are allowed in the key.

Examples:

- `mail.smtp.host` → group `mail`, key `smtp.host`
- `system.site_name` → group `system`, key `site_name`

Still invalid: empty path, missing first dot, leading/trailing/double dots, empty
group or empty key.

### `ConfigType::Encrypted`

Secrets use `ConfigType::Encrypted` and Laravel `Crypt` for storage. Prefer this
type for credentials instead of plaintext string/json values.

### `listOrdered()`

Returns **normalized** values (same rules as `get`), not raw Mongo documents.

### Bulk writes

- `setMany()` / bulk import bump the cache **once** per batch.
- Import CLI: `--dry-run` preview; replace without merge requires `--force`.

### `ConfigChanged` and secrets

Events no longer embed plaintext for encrypted values. The payload value is
`null` or a redacted placeholder.

### Facade binding

Prefer `JOOservices\LaravelConfig\Facades\Config` or the `ConfigStore` alias.
Do **not** bind this package as Laravel’s native `Config` facade/container key.

### Tooling removed

AI instruction sync tooling (`instructions:verify` / related scripts) is dropped.
Use repository `AGENTS.md` and `.github` guidance only.

## See also

- [CHANGELOG](CHANGELOG.md) (`[Unreleased]` / `4.0.0`)
- [Risks, legacy, and gaps](docs/05-maintenance/01-risks-legacy-and-gaps.md)
