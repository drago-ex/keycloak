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
			'clientSecret' => Expect::string(),
			'redirectUri' => Expect::string(),
			'encryptionAlgorithm' => Expect::string(),
			'encryptionKeyPath' => Expect::string(),
			'encryptionKey' => Expect::string(),
			'guzzleHttp' => Expect::array(),
			'version' => Expect::string(),
		]);
	}


	public function loadConfiguration(): void
	{
		$config = (array) $this->config;
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('guzzleHttp'))
			->setFactory(Client::class)
			->setArguments([(array) $config['guzzleHttp']]);

		$builder->addDefinition($this->prefix('sessions'))
			->setFactory(KeycloakSessions::class);

		$builder->addDefinition($this->prefix('keycloak'))
			->setFactory(Keycloak::class)
			->setArguments([(array) $this->config, ['httpClient' => '@keycloak.guzzleHttp']]);
	}
}
