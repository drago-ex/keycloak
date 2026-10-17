<?php

declare(strict_types=1);

use Drago\Keycloak\KeycloakAdapter;


/** Lets PHPStan analyse the KeycloakAdapter trait, which no class in the package uses. */
class TraitStub
{
	use KeycloakAdapter;
}
