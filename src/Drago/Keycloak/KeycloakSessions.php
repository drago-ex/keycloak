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
use Nette\SmartObject;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;


class KeycloakSessions
{
	use SmartObject;

	private const STATE = 'oauth2state';
	private const TOKEN = 'accessToken';
	private const RESOURCE = 'resourceOwner';
	private const BACKLINK = 'backlink';

	private SessionSection $sessionSection;


	public function __construct(
		private Session $session,
	) {
		$this->sessionSection = $this->session
			->getSection(self::class);
	}


	private function items(): array
	{
		return [
			self::STATE,
			self::TOKEN,
			self::RESOURCE,
			self::BACKLINK,
		];
	}


	public function getItems(): Items
	{
		$items = [];
		foreach ($this->items() as $item) {
			$items[$item] = $this->sessionSection->get($item);
		}

		return new Items(
			$items[self::STATE],
			$items[self::TOKEN],
			$items[self::RESOURCE],
			$items[self::BACKLINK],
		);
	}


	public function addAuthState(string $state, ?string $backlink): void
	{
		$this->sessionSection
			->set(self::STATE, $state);

		if ($backlink) {
			$this->sessionSection
				->set(self::BACKLINK, $backlink);
		}
	}


	public function removeAuthState(): void
	{
		$this->sessionSection
			->remove(self::STATE);
	}


	public function addAccessToken(AccessTokenInterface $accessToken): void
	{
		$this->sessionSection
			->set(self::TOKEN, $accessToken);
	}


	public function addResourceOwner(KeycloakResourceOwner $resource): void
	{
		$this->sessionSection
			->set(self::RESOURCE, $resource);
	}


	public function remove(): void
	{
		foreach ($this->items() as $item) {
			$this->sessionSection->remove($item);
		}
	}
}
