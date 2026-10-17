# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/keycloak`
- **Type**: library
- **Purpose**: Simple Keycloak adapter for easy integration.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: nette/application (^3.1), nette/http (^3.1), nette/di (^3.0), nette/security (^3.2), stevenmaguire/oauth2-keycloak (^6.1)
- **License**: MIT

## Repository Layout
- `src/`: package source code.
- `tests/`: automated tests.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Key Parts
- `DI\KeycloakExtension` builds the provider; the HTTP request is autowired and the five connection options are required.
- `KeycloakAdapter` runs on presenter startup: redirect to Keycloak, one-time state check, token exchange, resource owner. Provider errors become a generic 403 `BadRequestException` with the original as the previous exception.
- `KeycloakSessions` stores the state, token, resource owner and backlink in the session.
- Tests use a fake provider (`tests/Keycloak/KeycloakAdapter.phpt`); no Keycloak server is needed.

## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include nette/tester (^2.3), nette/bootstrap (^3.0), tracy/tracy (^2.10), phpstan/phpstan (^2.0), phpstan/phpstan-nette (^2.0).

## Essential Commands

```bash
composer tester                 # tester tests -s
composer phpstan                # phpstan analyse
```

## Conventions & Rules

- Preserve backward compatibility unless the task explicitly permits a breaking change. Check callers and public signatures before changing behavior.
- Follow the existing PHP style: strict types, explicit parameter and return types, tabs for indentation, and single-quoted strings where practical. Match nearby code when a convention is unclear.
- Keep package-specific configuration, resources, and migrations in their established directories. Do not edit generated or consuming-project files without confirming they are part of this repository’s intended output.
- Add or update focused tests for behavioral changes when this package has a runnable test setup. Do not invent test commands; use only the scripts and configuration present here.
- Update README examples and Composer metadata when a public API or supported requirement changes.
