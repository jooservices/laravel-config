# jooservices/laravel-config

This file adds project-only rules.

- PHP `^8.5`, Laravel package: `laravel/framework` `^12|^13`, MongoDB via `mongodb/laravel-mongodb` `^5.7`
- Namespace **must** be `JOOservices\LaravelConfig\` (uppercase `OO`)
- Persist runtime application config in MongoDB only (typed store + cache). No file-config replacement for Laravel's `config/` tree
- Pint `per`; PHPStan + Larastan; PHPCS + PHPMD; CaptainHook required — never `--no-verify`
- Commands: `composer lint`, `composer test`, `composer check`, `composer ci`
- Next planned release: **v4.0.0** (breaking). Do not tag until code upgrades in `UPGRADE-4.0.md` are complete and version is confirmed
