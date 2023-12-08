<?php

declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Keycloak;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;


class KeycloakSessions
{
	private const State = 'oauth2state';
	private const Token = 'accessToken';
	private const Resource = 'resourceOwner';
	private const Backlink = 'backlink';

	private SessionSection $sessionSection;


	public function __construct(
		private readonly Session $session,
	) {
		$this->sessionSection = $this->session
			->getSection(self::class);
	}


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
		$items = [];
		foreach ($this->items() as $item) {
			$items[$item] = $this->sessionSection->get($item);
		}

		return new Items(
			$items[self::State],
			$items[self::Token],
			$items[self::Resource],
			$items[self::Backlink],
		);
	}


	public function addAuthState(string $state, ?string $backlink): void
	{
		$this->sessionSection
			->set(self::State, $state);

		if ($backlink) {
			$this->sessionSection
				->set(self::Backlink, $backlink);
		}
	}


	public function removeAuthState(): void
	{
		$this->sessionSection
			->remove(self::State);
	}


	public function addAccessToken(AccessTokenInterface $accessToken): void
	{
		$this->sessionSection
			->set(self::Token, $accessToken);
	}


	public function addResourceOwner(KeycloakResourceOwner $resource): void
	{
		$this->sessionSection
			->set(self::Resource, $resource);
	}


	public function remove(): void
	{
		foreach ($this->items() as $item) {
			$this->sessionSection->remove($item);
		}
	}
}
