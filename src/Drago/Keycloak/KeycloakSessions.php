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


/**
 * Manages Keycloak session data including auth state, access token, and resource owner.
 */
class KeycloakSessions
{
	private const string State = 'oauth2state';
	private const string Token = 'accessToken';
	private const string Resource = 'resourceOwner';
	private const string Backlink = 'backlink';

	private SessionSection $sessionSection;


	/**
	 * Constructor to initialize the session section.
	 *
	 * @param Session $session The Nette session to be used for storing data.
	 */
	public function __construct(
		private readonly Session $session,
	) {
		$this->sessionSection = $this->session
			->getSection(self::class);  // Initialize the session section specific to this class.
	}


	/**
	 * Returns the list of session item keys.
	 *
	 * @return string[] The list of keys for the session items.
	 */
	private function items(): array
	{
		return [
			self::State,
			self::Token,
			self::Resource,
			self::Backlink,
		];
	}


	/**
	 * Retrieves session items (auth state, token, resource owner, backlink).
	 *
	 * @return Items The session items encapsulated in the Items object.
	 */
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


	/**
	 * Adds auth state and optionally a backlink to the session.
	 *
	 * @param string $state The OAuth2 state to be stored in the session.
	 * @param string|null $backlink An optional URL to be stored as the backlink.
	 */
	public function addAuthState(string $state, ?string $backlink): void
	{
		$this->sessionSection
			->set(self::State, $state);

		if ($backlink) {
			$this->sessionSection
				->set(self::Backlink, $backlink);
		}
	}


	/**
	 * Removes the auth state from the session.
	 */
	public function removeAuthState(): void
	{
		$this->sessionSection
			->remove(self::State);
	}


	/**
	 * Adds an access token to the session.
	 *
	 * @param AccessTokenInterface $accessToken The OAuth2 access token to store.
	 */
	public function addAccessToken(AccessTokenInterface $accessToken): void
	{
		$this->sessionSection
			->set(self::Token, $accessToken);
	}


	/**
	 * Adds a resource owner object to the session.
	 *
	 * @param KeycloakResourceOwner $resource The Keycloak resource owner to store.
	 */
	public function addResourceOwner(KeycloakResourceOwner $resource): void
	{
		$this->sessionSection
			->set(self::Resource, $resource);
	}


	/**
	 * Removes all session data associated with Keycloak authentication.
	 */
	public function remove(): void
	{
		foreach ($this->items() as $item) {
			$this->sessionSection->remove($item);
		}
	}
}
