# Drago Keycloak

Sign-in through Keycloak for Nette: a DI extension for the OAuth2 provider and a presenter trait that
handles the redirect, the state and PKCE check and the token exchange.

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

	# required; an absolute URL, or a path completed with the host of the current request;
	# it must lead to a presenter using KeycloakAdapter (see Redirect URI)
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
- checks the returned state and the stored verifier and answers 403 when one does not match or is missing; both are
  valid for one exchange only;
- exchanges the code for an access token and loads the resource owner into `KeycloakSessions`;
- calls `keycloakSignIn()` with the resource owner (empty by default, see below);
- keeps the `backlink` of the first request in `KeycloakSessions`, so the presenter can return there after sign-in
  (a sign-in without a backlink clears the one of an earlier sign-in).

Errors from Keycloak end with a generic 403 message; the original exception is kept as the previous
exception for logging.

Without an ACL check on startup, the presenter can sign the user in from the resource owner in `startup()`, for
example with your own authenticator (with an ACL check, see [Sign-in before an ACL check](#sign-in-before-an-acl-check-drago-expermission)):

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

### Example of `KeycloakAuthenticator`

The service gets the resource owner as an argument and returns the identity, which is passed to
`$user->login()`. It does not need to implement `Nette\Security\Authenticator`: its `authenticate(string $username,
string $password)` has no place for the Keycloak user, so such an authenticator would have to read it from
`KeycloakSessions` and be set with `$user->setAuthenticator()` before every `login()`.

```php
use Nette\Security\AuthenticationException;
use Nette\Security\SimpleIdentity;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;

final class KeycloakAuthenticator
{
	/** Attributes of the Keycloak user (user profile or token mappers) the application cannot work without. */
	private const array Required = ['name', 'email'];


	/** @throws AuthenticationException */
	public function authenticateOwner(KeycloakResourceOwner $owner): SimpleIdentity
	{
		$data = $owner->toArray();
		$missing = array_filter(
			self::Required,
			fn(string $attribute): bool => !is_string($data[$attribute] ?? null) || trim($data[$attribute]) === '',
		);

		if ($missing !== []) {
			throw new AuthenticationException('Missing Keycloak attributes: ' . implode(', ', $missing) . '.');
		}

		// roles of the application, e.g. from a realm or client role, a group or an attribute
		$roles = in_array('admin', $data['realm_access']['roles'] ?? [], true) ? ['admin'] : ['user'];

		return new SimpleIdentity((string) $owner->getId(), $roles, $data);
	}
}
```

Register it as a service, so the presenter gets it by autowiring:

```neon
services:
	- App\Model\User\KeycloakAuthenticator
```

The realm roles (`realm_access`) are in the user info only when the client has a mapper that adds them there
(*Client scopes* → `roles` → *Add to userinfo*); otherwise take the roles from your own database by `getId()`.

### Sign-in before an ACL check (drago-ex/permission)

The trait puts its handler first in `onStartup`, before the handlers registered earlier, e.g. the ACL check of the
`Authorization` trait of drago-ex/permission in a parent presenter (inject methods of a parent run first). A guest
therefore reaches Keycloak and the code is exchanged before the ACL check runs. That check, however, comes before
`startup()`, so signing in in `startup()` (above) is too late: the ACL still sees a guest and redirects it to the
sign-in destination, back to the presenter and Keycloak, in a loop. Sign the user in by overriding
`keycloakSignIn()`; the trait calls it right after Keycloak returned the user, before the other startup handlers:

```php
use Drago\Keycloak\KeycloakAdapter;
use Drago\Keycloak\KeycloakSessions;
use Nette\Security\AuthenticationException;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;

class BackendPresenter extends BasePresenter // BasePresenter uses Authorization
{
	use KeycloakAdapter;

	public function __construct(
		private readonly Keycloak $keycloak,
		private readonly KeycloakSessions $keycloakSessions,
		private readonly KeycloakAuthenticator $authenticator,
	) {
		parent::__construct();
	}


	protected function keycloakSignIn(KeycloakResourceOwner $resourceOwner): void
	{
		try {
			$this->getUser()->login($this->authenticator->authenticateOwner($resourceOwner));

		} catch (AuthenticationException $e) {
			// a redirect would only bring the same user back from Keycloak, so the user gets 403
			$this->getUser()->logout(true);
			$this->keycloakSessions->remove();
			$this->getHttpResponse()->setHeader('Refresh', '6; url=' . $this->keycloak->getLogoutUrl());
			$this->error($e->getMessage(), 403);
		}

		$this->restoreRequest($this->keycloakSessions->getItems()->backlink ?? '');
		$this->redirect('this');
	}
}
```

Keep the sign-in destination of the ACL check (`signInDestination()`) on a presenter that uses this trait, so a
guest goes to Keycloak and not to a sign-in form.

### Redirect URI

Keycloak returns the user with `code` and `state` to `redirectUri`. Only a presenter using this trait exchanges
the code, so `redirectUri` must lead to one of them (e.g. `/admin/`, not the home page of a front module without the
trait); otherwise the code is never exchanged and the user is not signed in. Add the same URL (or a pattern such as
`https://example.com/admin/*`) to *Valid redirect URIs* of the client in Keycloak.

### Error message in `@layout.latte`

For the sign-in in `startup()` (the first example), which sets `$userLoginError` and renders the page. The sign-in
in `keycloakSignIn()` ends with 403 and the error page instead, as an ACL check would redirect a guest before the
page renders.

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
// State, PKCE verifier, access token, resource owner and backlink
$items = $this->keycloakSessions->getItems();
```

## User Logout Method

```php
$this->getUser()->logout();
$this->keycloakSessions->remove();
$this->redirectUrl($this->keycloak->getLogoutUrl());
```

For example as a signal of the presenter with the trait, linked as `<a n:href="signOut!">Sign out</a>`. The user is
logged out of the application and of Keycloak too; without the Keycloak logout the next request would sign the
same user in again without asking.

```php
public function handleSignOut(): void
{
	$this->getUser()->logout(true);
	$this->keycloakSessions->remove();
	$this->redirectUrl($this->keycloak->getLogoutUrl());
}
```

With an ACL check of signals (drago-ex/permission), allow the `signOut` privilege for the roles of signed-in users.
