## Drago Keycloak
Simple Keycloak adapter for easy integration.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://raw.githubusercontent.com/drago-ex/keycloak/master/license.md)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fkeycloak.svg)](https://badge.fury.io/ph/drago-ex%2Fkeycloak)
[![Tests](https://github.com/drago-ex/keycloak/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/keycloak/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/keycloak/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/keycloak/actions/workflows/coding-style.yml)
[![CodeFactor](https://www.codefactor.io/repository/github/drago-ex/keycloak/badge)](https://www.codefactor.io/repository/github/drago-ex/keycloak)
[![Coverage Status](https://coveralls.io/repos/github/drago-ex/keycloak/badge.svg?branch=master)](https://coveralls.io/github/drago-ex/keycloak?branch=master)

## Technology
- PHP 8.3 or higher
- composer

## Installation
```
composer require drago-ex/keycloak
```

## Extension registration in `config.neon`
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
	# version: 21.0.1
	# encryptionAlgorithm: 'RS256'
	# encryptionKeyPath: '../key.pem'
	# encryptionKey: 'contents_of_key_or_certificate'

	# https://github.com/guzzle/guzzle
	# guzzleHttp:
```

## Usage in Presenter
```php
use Drago\Keycloak\KeycloakAdapter;

public function __construct(
  private Keycloak $keycloak,
  private KeycloakSessions $keycloakSessions,
) {
  parent::__construct();
}

// Simple login
protected function startup(): void
{
  parent::startup();
  if (!$this->getUser()->isLoggedIn()) {
    $keycloakUser = $this->keycloakSessions->getItems()->resourceOwner;
    $this->getUser()->login($keycloakUser->getName(), $keycloakUser->getId());
    $this->redirect('redirect');
  }
}

// Custom authentication with Keycloak attributes and backlink
protected function startup(): void
{
  parent::startup();
  if (!$this->getUser()->isLoggedIn()) {
    $keycloakUser = $this->keycloakSessions->getItems()->resourceOwner;

    try {
      if ($keycloakUser) {
        $user = $this->getUser();
        
        // Custom authenticator
        $user->setAuthenticator($this->authRepository);

        // User login
        $user->login($keycloakUser->getName(), $keycloakUser->getId());

        // Backlink handling
        $this->restoreRequest($this->backlink);
        $this->redirect(':Backend:Admin:');
      }

    } catch (AuthenticationException $e) {
      if ($e->getCode() === 1) {
        $this->template->userLoginError = true;
        $this->getUserLogout();
        $redirect = $this->keycloak->getLogoutUrl();
        header('refresh:6; url=' . $redirect);
      }
    }
  }
}

// User logout
private function getUserLogout(): void
{
  $this->getUser()->logout();
  $this->keycloakSessions->remove();
}
```

### Error message in `@layout.latte`
```latte
<body n:ifset="$userLoginError">
	<h1 class="text-danger text-center mt-5">
		{_'The user does not have the required attributes set in keycloak.'}
	</h1>
</body>
<body n:if="$user->loggedIn">
	...
</body>
```

### Items from Keycloak
```php

// Get state, accessToken, and resource owner
$this->keycloakSessions->getItems();
```

## User Logout Method
```php
$this->keycloakSessions->remove();
$this->redirectUrl($this->keycloak->getLogoutUrl());
```
