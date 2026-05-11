# Contributing

## Branching

- normal work starts from the latest `develop`
- do not commit directly to `master` or `develop`
- feature and fix branches target `develop`
- release branches target `master` and `master` merges back into `develop`

## Local workflow

```bash
git checkout develop
git pull origin develop
git checkout -b feature/<short-name>
```

## Validation

```bash
composer lint
composer lint:all
composer test
composer check
```

MongoDB is required for the integration test suite.

## Package-specific rules

- use the `JOOservices\LaravelConfig\` namespace
- keep package logic in services and thin commands or facades
- do not mock internal package persistence behavior in tests
- Pint is the formatting authority when tools disagree
- stop and ask when requirements are unclear or risky
