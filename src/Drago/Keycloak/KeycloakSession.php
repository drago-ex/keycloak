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


class KeycloakSession
{
	use SmartObject;

	/** Name for resource owner. */
	private const RESOURCE = 'resourceOwner';

	/** Name for access token  */
	private const TOKEN = 'accessToken';

	private SessionSection $sessionSection;


	public function __construct(
		private Session $session,
	) {
		$this->sessionSection = $this->session
			->getSection(self::class);
	}


	/**
	 * Save resource owner items.
	 */
	public function setResourceOwner(KeycloakResourceOwner $resource): void
	{
		$this->sessionSection
			->set(self::RESOURCE, $resource);
	}


	/**
	 * Returns the resource owner items.
	 * @return KeycloakResourceOwner[]
	 */
	public function getResourceOwner(): array
	{
		return $this->sessionSection
			->get(self::RESOURCE) ?? [];
	}


	/**
	 * Save access token.
	 */
	public function setAccessToken(AccessTokenInterface $accessToken): void
	{
		$this->sessionSection
			->set(self::TOKEN, $accessToken);
	}


	/**
	 * Returns access token.
	 * @return AccessTokenInterface[]
	 */
	public function getAccessToken(): array
	{
		return$this->sessionSection
			->get(self::TOKEN) ?? [];
	}


	/**
	 * Remove all items.
	 */
	public function remove(): void
	{
		$items = [
			self::TOKEN,
			self::RESOURCE,
		];
		foreach ($items as $item) {
			$this->sessionSection->remove($item);
		}
	}
}
