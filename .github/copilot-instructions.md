# Copilot Instructions For `jooservices/laravel-config`

Read [AGENTS.md](../AGENTS.md) as the primary repository policy.

When generating or editing code:

- preserve Laravel package discovery and the `JOOservices\LaravelConfig\` namespace
- keep MongoDB persistence and cache behavior configurable
- keep scope limited to typed application config storage (not Laravel file config)
- prefer the package `Config` / `ConfigStore` facade — do not bind as Laravel `Config`
- update tests and docs when public behavior or commands change
- use `composer lint`, `composer test`, and `composer check` as the main quality commands
- inspect the current source, branch, and docs before proposing changes
- use `develop` for normal work and target pull requests to `develop`; reserve `master` for release and hotfix work
- stop and ask when requirements, source truth, docs, CI, or package scope conflict
- consider work complete only after relevant validation passes or an exact environment limitation is reported
