<?php

declare(strict_types=1);

namespace Drago\Keycloak;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;


/** Manages Keycloak session data including auth state, access token, and resource owner. */
class KeycloakSessions
{
	private const string State = 'oauth2state';
	private const string Token = 'accessToken';
	private const string Resource = 'resourceOwner';
	private const string Backlink = 'backlink';

	private SessionSection $sessionSection;


	public function __construct(
		private readonly Session $session,
	) {
		$this->sessionSection = $this->session->getSection(self::class);
	}


	/** @return list<string> */
	private function items(): array
	{
		return [
			self::State,
			self::Token,
			self::Resource,
			self::Backlink,
		];
	}


	public function getItems(): Items
	{
		$state = $this->sessionSection->get(self::State);
		$token = $this->sessionSection->get(self::Token);
		$resource = $this->sessionSection->get(self::Resource);
		$backlink = $this->sessionSection->get(self::Backlink);

		return new Items(
			state: is_string($state) ? $state : null,
			accessToken: $token instanceof AccessTokenInterface ? $token : null,
			resourceOwner: $resource instanceof KeycloakResourceOwner ? $resource : null,
			backlink: is_string($backlink) ? $backlink : null,
		);
	}


	public function addAuthState(string $state, ?string $backlink): void
	{
		$this->sessionSection->set(self::State, $state);

		if ($backlink) {
			$this->sessionSection->set(self::Backlink, $backlink);
		}
	}


	public function removeAuthState(): void
	{
		$this->sessionSection->remove(self::State);
	}


	public function addAccessToken(AccessTokenInterface $accessToken): void
	{
		$this->sessionSection->set(self::Token, $accessToken);
	}


	public function addResourceOwner(KeycloakResourceOwner $resource): void
	{
		$this->sessionSection->set(self::Resource, $resource);
	}


	public function remove(): void
	{
		foreach ($this->items() as $item) {
			$this->sessionSection->remove($item);
		}
	}
}
