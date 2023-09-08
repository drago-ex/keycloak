<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Keycloak\DI;

use Drago\Keycloak\KeycloakSessions;
use GuzzleHttp\Client;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;


class KeycloakExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'authServerUrl' => Expect::string(),
			'realm' => Expect::string(),
			'clientId' => Expect::string(),
			'clientSecret' => Expect::string()->nullable(),
			'redirectUri' => Expect::string(),
			'encryptionAlgorithm' => Expect::string()->nullable(),
			'encryptionKeyPath' => Expect::string()->nullable(),
			'encryptionKey' => Expect::string()->nullable(),
			'version' => Expect::string()->nullable(),
			'guzzleHttp' => Expect::array(),
		]);
	}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('guzzleHttp'))
			->setFactory(Client::class)
			->setArguments([(array) $this->config['guzzleHttp']]);

		$builder->addDefinition($this->prefix('sessions'))
			->setFactory(KeycloakSessions::class);

		$builder->addDefinition($this->prefix('keycloak'))
			->setFactory(Keycloak::class)
			->setArguments([(array) $this->config, ['httpClient' => '@keycloak.guzzleHttp']]);
	}
}
