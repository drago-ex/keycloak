<p align="center">
  <img src="https://avatars0.githubusercontent.com/u/11717487?s=400&u=40ecb522587ebbcfe67801ccb6f11497b259f84b&v=4" width="100" alt="logo">
</p>

<h3 align="center">Drago Extension</h3>
<p align="center">Simple packages built on Nette Framework</p>

## Drago Authorization
Simple keycloak adapter.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://raw.githubusercontent.com/drago-ex/keycloak/master/license.md)

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

## Use trait in presenter
```php
use Drago\Keycloak\KeycloakAdapter
```

## Identity is stored in sessions
```php
$session = $this->getSession()->getSection('keycloak');
$user = $session->get('owner');
Debugger::barDump($user);
```

We will create the login method ourselves as needed.
