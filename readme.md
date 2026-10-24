# Drago Keycloak

English | [Čeština](readme.cs.md)

Simple Keycloak adapter for easy integration.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/keycloak/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fkeycloak.svg)](https://badge.fury.io/ph/drago-ex%2Fkeycloak)
[![Tests](https://github.com/drago-ex/keycloak/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/keycloak/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/keycloak/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/keycloak/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- Nette Framework
- Composer

## Installation

```
composer require drago-ex/keycloak
```

## Extension Registration

```neon
extensions:
	keycloak: Drago\Keycloak\DI\KeycloakExtension


keycloak:

	# https://github.com/stevenmaguire/oauth2-keycloak
	# required
	authServerUrl: keycloak-server-url
	realm: keycloak-realm
	clientId: keycloak-client-id

	# required; an absolute URL, or a path completed with the host of the current request
	redirectUri: https://example.com/callback-url

	# optional
	# only for a confidential client (Client authentication on); a public client has no secret
	# clientSecret: keycloak-client-secret
	# version: 21.0.1
	# encryptionAlgorithm: 'RS256'
	# encryptionKeyPath: '../key.pem'
	# encryptionKey: 'contents_of_key_or_certificate'

	# https://github.com/guzzle/guzzle
	# guzzleHttp:
```

## Usage in Presenter

Use the `KeycloakAdapter` trait in a presenter. On startup the trait:

- redirects a guest without an authorization code to Keycloak and stores the OAuth state and the PKCE code verifier
  (S256; Keycloak gets only its hash, so a stolen code cannot be exchanged, also for a public client);
- checks the returned state and answers 403 when it does not match; the state is valid for one exchange only;
- exchanges the code for an access token and loads the resource owner into `KeycloakSessions`;
- keeps the `backlink` of the first request in `KeycloakSessions`, so the presenter can return there after sign-in.

Errors from Keycloak end with a generic 403 message; the original exception is kept as the previous
exception for logging.

The presenter then signs the user in from the resource owner, for example with your own authenticator:

```php
use Drago\Keycloak\KeycloakAdapter;
use Drago\Keycloak\KeycloakSessions;
use Nette\Application\UI\Presenter;
use Nette\Security\AuthenticationException;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

final class AdminPresenter extends Presenter
{
	use KeycloakAdapter;

	public function __construct(
		private readonly Keycloak $keycloak,
		private readonly KeycloakSessions $keycloakSessions,
		private readonly KeycloakAuthenticator $authenticator,
	) {
		parent::__construct();
	}


	protected function startup(): void
	{
		parent::startup();
		$keycloakUser = $this->keycloakSessions->getItems()->resourceOwner;
		if ($this->getUser()->isLoggedIn() || $keycloakUser === null) {
			return;
		}

		try {
			$this->getUser()->login($this->authenticator->authenticateOwner($keycloakUser));
			$this->restoreRequest($this->keycloakSessions->getItems()->backlink ?? '');
			$this->redirect('this');

		} catch (AuthenticationException) {
			$this->template->userLoginError = true;
			$this->signOut();
			$this->getHttpResponse()->setHeader('Refresh', '6; url=' . $this->keycloak->getLogoutUrl());
		}
	}


	private function signOut(): void
	{
		$this->getUser()->logout();
		$this->keycloakSessions->remove();
	}
}
```

`KeycloakAuthenticator` stands for your own service that maps the Keycloak user (`getId()`, `getName()`,
`getEmail()`, `toArray()`) to an identity of your application and throws `AuthenticationException` when
the user is not allowed in.

### Error message in `@layout.latte`

```latte
<body>
{ifset $userLoginError}
	<h1 class="text-danger text-center mt-5">
		The user does not have the required attributes set in Keycloak.
	</h1>
{else}
	{if $user->isLoggedIn()}
		...
	{/if}
{/ifset}
</body>
```

### Items from Keycloak

```php
// State, access token, resource owner and backlink
$items = $this->keycloakSessions->getItems();
```

## User Logout Method

```php
$this->getUser()->logout();
$this->keycloakSessions->remove();
$this->redirectUrl($this->keycloak->getLogoutUrl());
```
