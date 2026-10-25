# Drago Keycloak

[English](readme.md) | Čeština

Jednoduchý adaptér pro snadné zapojení Keycloaku.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/keycloak/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fkeycloak.svg)](https://badge.fury.io/ph/drago-ex%2Fkeycloak)
[![Tests](https://github.com/drago-ex/keycloak/actions/workflows/tests.yml/badge.svg)](https://github.com/drago-ex/keycloak/actions/workflows/tests.yml)
[![Coding Style](https://github.com/drago-ex/keycloak/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/keycloak/actions/workflows/coding-style.yml)

## Požadavky

- PHP >= 8.3, < 9
- Nette Framework
- Composer

## Instalace

```
composer require drago-ex/keycloak
```

## Registrace rozšíření

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

## Použití v presenteru

Použijte v presenteru trait `KeycloakAdapter`. Při startu trait:

- přesměruje návštěvníka bez autorizačního kódu do Keycloaku a uloží stav OAuth a ověřovací kód PKCE
  (S256; Keycloak dostane jen jeho otisk, takže ukradený kód nejde vyměnit, ani u veřejného klienta);
- zkontroluje vrácený stav i uložený ověřovací kód a při neshodě nebo chybějícím kódu odpoví 403; oba platí jen pro
  jednu výměnu;
- vymění kód za přístupový token a načte vlastníka zdroje do `KeycloakSessions`;
- uloží `backlink` prvního požadavku do `KeycloakSessions`, aby se presenter mohl po přihlášení vrátit zpět
  (přihlášení bez backlinku smaže ten z dřívějšího přihlášení).

Chyby z Keycloaku skončí obecnou zprávou 403; původní výjimka zůstane jako předchozí
výjimka pro logování.

Presenter pak uživatele přihlásí podle vlastníka zdroje, například vlastním authenticatorem:

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

`KeycloakAuthenticator` zastupuje vaši vlastní službu, která převede uživatele z Keycloaku (`getId()`, `getName()`,
`getEmail()`, `toArray()`) na identitu vaší aplikace a vyhodí `AuthenticationException`, když
uživatel nemá povolený přístup.

### Chybová zpráva v `@layout.latte`

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

### Údaje z Keycloaku

```php
// State, PKCE verifier, access token, resource owner and backlink
$items = $this->keycloakSessions->getItems();
```

## Odhlášení uživatele

```php
$this->getUser()->logout();
$this->keycloakSessions->remove();
$this->redirectUrl($this->keycloak->getLogoutUrl());
```
