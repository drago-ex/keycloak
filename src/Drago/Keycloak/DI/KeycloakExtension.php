<?php

declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Keycloak\DI;

use Drago\Keycloak\KeycloakSessions;
use GuzzleHttp\Client;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;


/**
 * Compiler extension for configuring Keycloak integration.
 * This extension allows you to configure the Keycloak authentication client.
 */
class KeycloakExtension extends CompilerExtension
{
	/**
	 * Defines the configuration schema for the extension.
	 *
	 * @return Schema The configuration schema that defines the expected structure.
	 */
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'authServerUrl' => Expect::string()->required(),  // URL of the authentication server
			'realm' => Expect::string()->required(),          // Keycloak realm name
			'clientId' => Expect::string()->required(),       // Client ID for the OAuth2 authentication
			'clientSecret' => Expect::string()->required(),   // Client secret for the OAuth2 authentication
			'redirectUri' => Expect::string()->required(),    // Redirect URI after authentication
			'encryptionAlgorithm' => Expect::string(),        // Encryption algorithm (optional)
			'encryptionKeyPath' => Expect::string(),          // Path to encryption key (optional)
			'encryptionKey' => Expect::string(),              // Encryption key (optional)
			'guzzleHttp' => Expect::array(),                  // Configuration for Guzzle HTTP client
			'version' => Expect::string()->default('v1'),     // Version of the Keycloak API
		]);
	}


	/**
	 * Loads the configuration and registers the services in the DI container.
	 *
	 * @return void
	 */
	public function loadConfiguration(): void
	{
		$config = (array) $this->config;
		$builder = $this->getContainerBuilder();

		// Guzzle HTTP client definition
		$builder->addDefinition($this->prefix('guzzleHttp'))
			->setFactory(Client::class)
			->setArguments([$config['guzzleHttp']]);

		// Keycloak session management definition
		$builder->addDefinition($this->prefix('sessions'))
			->setFactory(KeycloakSessions::class);

		// Keycloak provider definition with configuration and HTTP client injection
		$builder->addDefinition($this->prefix('keycloak'))
			->setFactory(Keycloak::class)
			->setArguments([$config, ['httpClient' => '@keycloak.guzzleHttp']]);
	}
}
