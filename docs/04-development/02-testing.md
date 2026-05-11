# Testing

The package test suite is integration-oriented.

## Rules

- use real MongoDB-backed persistence tests where practical
- do not mock internal package behavior such as `ConfigService`, the facade, or the MongoDB model flow
- use mocks or fakes only for real external boundaries if needed

## Commands

```bash
composer test
composer test:coverage
```
