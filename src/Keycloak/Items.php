<?php

declare(strict_types=1);

namespace Drago\Keycloak;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;


class Items
{
	public function __construct(
		public ?string $state,
		public ?AccessTokenInterface $accessToken,
		public ?KeycloakResourceOwner $resourceOwner,
		public ?string $backlink,
	) {
	}
}
