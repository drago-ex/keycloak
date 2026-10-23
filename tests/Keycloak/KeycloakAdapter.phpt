<?php

/**
 * Test: Drago\Keycloak\KeycloakAdapter
 */

declare(strict_types=1);

use Drago\Keycloak\KeycloakAdapter;
use Drago\Keycloak\KeycloakSessions;
use League\OAuth2\Client\Token\AccessToken;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Nette\Http;
use Nette\Security\User;
use Nette\Security\UserStorage;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class FakeKeycloak extends Keycloak
{
	public bool $failToken = false;


	public function __construct()
	{
		parent::__construct(['authServerUrl' => 'https://sso.example.com', 'realm' => 'realm', 'clientId' => 'client', 'clientSecret' => 'secret', 'redirectUri' => 'https://app.example.com/']);
	}


	public function getAuthorizationUrl(array $options = []): string
	{
		$this->state = 'expected-state';
		return 'https://sso.example.com/auth';
	}


	public function getAccessToken($grant, array $options = []): AccessToken
	{
		if ($this->failToken) {
			throw new RuntimeException('Secret provider detail');
		}

		return new AccessToken(['access_token' => 'token-' . $options['code']]);
	}


	public function getResourceOwner(AccessToken $token): KeycloakResourceOwner
	{
		return new KeycloakResourceOwner(['sub' => 'user-1', 'name' => 'John']);
	}
}


class MemoryUserStorage implements UserStorage
{
	public function saveAuthentication(Nette\Security\IIdentity $identity): void
	{
	}


	public function clearAuthentication(bool $clearIdentity): void
	{
	}


	public function getState(): array
	{
		return [false, null, null];
	}


	public function setExpiration(?string $expire, bool $clearIdentity): void
	{
	}
}


class KeycloakPresenter extends Presenter
{
	use KeycloakAdapter;

	/** @param array<string, string> $params */
	public function setParams(array $params): void
	{
		(new ReflectionProperty(Nette\Application\UI\Component::class, 'params'))->setValue($this, $params);
	}
}


/** @return array{KeycloakPresenter, FakeKeycloak, KeycloakSessions} */
function createPresenter(array $params): array
{
	$request = new Http\Request(new Http\UrlScript('https://app.example.com/'));
	$response = new Http\Response;
	$session = new Http\Session($request, $response);
	$sessions = new KeycloakSessions($session);
	$keycloak = new FakeKeycloak;

	$presenter = new KeycloakPresenter;
	$presenter->injectPrimary($request, $response, session: $session, user: new User(new MemoryUserStorage));
	$presenter->setParams($params);
	$presenter->injectKeycloakAuth($presenter, $keycloak, $sessions);
	return [$presenter, $keycloak, $sessions];
}


test('A guest without a code is redirected to Keycloak and the state is stored', function () {
	[$presenter, , $sessions] = createPresenter(['backlink' => 'abc']);

	Assert::exception(fn() => $presenter->onStartup(), AbortException::class);
	Assert::same('expected-state', $sessions->getItems()->state);
	Assert::same('abc', $sessions->getItems()->backlink);
});


test('A code with a wrong state is rejected and the state is removed', function () {
	[$presenter, , $sessions] = createPresenter(['code' => 'c1', 'state' => 'forged']);
	$sessions->addAuthState('expected-state', null);

	$e = Assert::exception(fn() => $presenter->onStartup(), BadRequestException::class);
	Assert::same(403, $e->getCode());
	Assert::null($sessions->getItems()->state);
	Assert::null($sessions->getItems()->accessToken);
});


test('A valid code stores the token and the resource owner, and the state cannot be reused', function () {
	[$presenter, , $sessions] = createPresenter(['code' => 'c1', 'state' => 'expected-state']);
	$sessions->addAuthState('expected-state', 'back');

	$presenter->onStartup();

	$items = $sessions->getItems();
	Assert::same('token-c1', $items->accessToken?->getToken());
	Assert::same('user-1', $items->resourceOwner?->getId());
	Assert::same('back', $items->backlink);
	Assert::null($items->state);
});


test('A failed token request shows a generic message and keeps the cause for logs', function () {
	[$presenter, $keycloak, $sessions] = createPresenter(['code' => 'c1', 'state' => 'expected-state']);
	$sessions->addAuthState('expected-state', null);
	$keycloak->failToken = true;

	$e = Assert::exception(fn() => $presenter->onStartup(), BadRequestException::class, 'Failed to get access token.', 403);
	Assert::same('Secret provider detail', $e->getPrevious()?->getMessage());
});


test('Removing the session data clears everything', function () {
	[, , $sessions] = createPresenter([]);
	$sessions->addAuthState('state', 'back');
	$sessions->addAccessToken(new AccessToken(['access_token' => 't']));
	$sessions->remove();

	$items = $sessions->getItems();
	Assert::null($items->state);
	Assert::null($items->accessToken);
	Assert::null($items->backlink);
});
