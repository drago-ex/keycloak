<?php

declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Keycloak;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;


/**
 * Holds the items related to the Keycloak authentication session.
 * These items include the state, access token, resource owner, and backlink.
 */
class Items
{
	/**
	 * Constructor for initializing Keycloak session items.
	 *
	 * @param string|null $state The OAuth2 state parameter for CSRF protection.
	 * @param AccessTokenInterface|null $accessToken The access token from Keycloak.
	 * @param KeycloakResourceOwner|null $resourceOwner The Keycloak resource owner (user details).
	 * @param string|null $backlink The URL to redirect to after authentication.
	 */
	public function __construct(
		public ?string $state,
		public ?AccessTokenInterface $accessToken,
		public ?KeycloakResourceOwner $resourceOwner,
		public ?string $backlink,
	) {
	}
}
