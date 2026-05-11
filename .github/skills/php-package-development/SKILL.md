# PHP Package Development

## Purpose

Use this skill when changing package code, service wiring, config behavior, or operator commands.

## Rules

- keep business logic in `ConfigService`
- keep the service provider and commands thin
- prefer integration tests over internal mocking
- MongoDB is the persistence boundary and must be available in tests
- config paths must validate as `group.key`
- document multi-process stale-memory limitations when behavior depends on in-memory state
