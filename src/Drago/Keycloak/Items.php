<?php

declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Keycloak;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Nette\SmartObject;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;


class Items
{
	use SmartObject;

	public function __construct(
		public ?string $state,
		public ?AccessTokenInterface $accessToken,
		public ?KeycloakResourceOwner $resourceOwner,
	) {
	}
}
