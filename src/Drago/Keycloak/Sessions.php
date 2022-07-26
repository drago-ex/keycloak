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


class Sessions
{
	use SmartObject;

	private const STATE = 'oauth2state';
	private const TOKEN = 'accessToken';
	private const RESOURCE = 'resourceOwner';

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
		);
	}


	public function addAuthState(string $state): void
	{
		$this->sessionSection
			->set(self::STATE, $state);
	}


	public function getAuthState(): string
	{
		return $this->sessionSection
			->get(self::STATE);
	}


	public function addAccessToken(AccessTokenInterface $accessToken): void
	{
		$this->sessionSection
			->set(self::TOKEN, $accessToken);
	}


	/**
	 * @return AccessTokenInterface[]
	 */
	public function getAccessToken(): array
	{
		return $this->sessionSection
				->get(self::TOKEN) ?? [];
	}


	public function addResourceOwner(KeycloakResourceOwner $resource): void
	{
		$this->sessionSection
			->set(self::RESOURCE, $resource);
	}


	/**
	 * @return KeycloakResourceOwner[]
	 */
	public function getResourceOwner(): array
	{
		return $this->sessionSection
				->get(self::RESOURCE) ?? [];
	}


	public function remove(): void
	{
		foreach ($this->items() as $item) {
			$this->sessionSection->remove($item);
		}
	}
}
