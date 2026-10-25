# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/keycloak`
- **Type**: library
- **Purpose**: Simple Keycloak adapter for easy integration.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: nette/application (^3.2), nette/http (^3.1), nette/di (^3.0), nette/schema (^1.2), nette/security (^3.2), stevenmaguire/oauth2-keycloak (^6.1)
- **License**: MIT

## Repository Layout
- `src/`: package source code.
- `tests/`: automated tests.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Key Parts
- `DI\KeycloakExtension` builds the provider; the HTTP request is autowired; `authServerUrl`, `realm`, `clientId` and `redirectUri` are required, `clientSecret` is optional (a public client has none; league/oauth2-client builds the token request with http_build_query, which leaves out the null `client_secret`, tested with a Guzzle MockHandler).
- `KeycloakAdapter` works in any `Presenter` (no base presenter, removed in 3.0; a persistent backlink would be added to every link) and runs on startup: redirect to Keycloak, one-time state check, token exchange, resource owner. Provider errors become a generic 403 `BadRequestException` with the original as the previous exception.
- PKCE (S256) is always on: `Pkce` creates the verifier and its challenge, the adapter adds `code_challenge` to the authorization URL, keeps the verifier with the state (one exchange only) and passes it with `setPkceCode()`, so league/oauth2-client sends `code_verifier`. A callback without the stored verifier is refused with 403 (`addAuthState()` requires it), and the verifier is reset to `''` after the exchange (league sends it only when not empty), so a later grant on the shared provider does not carry it (2026-10-25, code review). The Keycloak provider itself has no PKCE switch (`getPkceMethod()` returns null). Keycloak checks a challenge whenever one was sent, for public and confidential clients.
- `KeycloakSessions` stores the state, PKCE verifier, token, resource owner and backlink in the session; the presenter reads the backlink from `getItems()->backlink` after sign-in.
- Tests use a fake provider (`tests/Keycloak/KeycloakAdapter.phpt`); no Keycloak server is needed.

## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include nette/tester (^2.5), phpstan/phpstan (^2.0), phpstan/phpstan-nette (^2.0).

## Essential Commands

```bash
composer tester                 # tester tests -s -C
composer phpstan                # phpstan analyse
```

## Conventions & Rules

- Preserve backward compatibility unless the task explicitly permits a breaking change. Check callers and public signatures before changing behavior.
- Follow the existing PHP style: strict types, explicit parameter and return types, tabs for indentation, and single-quoted strings where practical. Match nearby code when a convention is unclear.
- Keep package-specific configuration, resources, and migrations in their established directories. Do not edit generated or consuming-project files without confirming they are part of this repository’s intended output.
- Add or update focused tests for behavioral changes when this package has a runnable test setup. Do not invent test commands; use only the scripts and configuration present here.
- Update README examples and Composer metadata when a public API or supported requirement changes.
