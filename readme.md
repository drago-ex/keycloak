## Drago Keycloak
Simple keycloak adapter.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://raw.githubusercontent.com/drago-ex/keycloak/master/license.md)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fkeycloak.svg)](https://badge.fury.io/ph/drago-ex%2Fkeycloak)
[![Coding Style](https://github.com/drago-ex/keycloak/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/keycloak/actions/workflows/coding-style.yml)

## Technology
- PHP 8.0 or higher
- composer

## Installation
```
composer require drago-ex/keycloak
```

## Extension registration
```neon
extensions:
	keycloak: Drago\Keycloak\DI\KeycloakExtension


keycloak:

	# https://github.com/stevenmaguire/oauth2-keycloak
	authServerUrl: keycloak-server-url
	realm: keycloak-realm
	clientId: keycloak-client-id
	clientSecret: keycloak-client-secret
	redirectUri: https://example.com/callback-url

	# optional
	# encryptionAlgorithm: 'RS256'
	# encryptionKeyPath: '../key.pem'
	# encryptionKey: 'contents_of_key_or_certificate'

	# https://github.com/guzzle/guzzle
	# guzzleHttp: []
```

## Use in presenter
```php
use Drago\Keycloak\KeycloakAdapter

public function __construct(
	private Keycloak $keycloak,
	private KeycloakSessions $keycloakSessions,
	) {
		parent::__construct();
	}
```

### Items from keycloak
```php

// state, accessToken and resource owner
$this->keycloakSessions->getItems();
```

## User logout method
```php
$this->keycloakSessions->remove();
$this->redirectUrl($this->keycloak->getLogoutUrl());
```
