<?php

declare(strict_types=1);

namespace Drago\Keycloak\DI;

use Drago\Keycloak\KeycloakSessions;
use GuzzleHttp\Client;
use Nette\DI\CompilerExtension;
use Nette\Http\Request;
use Nette\Http\Url;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;


class KeycloakExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'authServerUrl' => Expect::string()->required(),
			'realm' => Expect::string()->required(),
			'clientId' => Expect::string()->required(),
			'clientSecret' => Expect::string()->required(),
			'redirectUri' => Expect::string()->required(),
			'encryptionAlgorithm' => Expect::string(),
			'encryptionKeyPath' => Expect::string(),
			'encryptionKey' => Expect::string(),
			'guzzleHttp' => Expect::array(),
			'version' => Expect::string(),
		]);
	}


	/** @param array<string, mixed> $config */
	public static function createKeycloak(Request $request, array $config, Client $guzzleHttp): Keycloak
	{
		$redirectUri = isset($config['redirectUri']) && is_string($config['redirectUri'])
			? $config['redirectUri']
			: '';

		$redirectUrl = new Url($redirectUri);
		if ($redirectUrl->getHostUrl() === '') {
			$config['redirectUri'] = $request->getUrl()
				->withPath($redirectUrl->getPath())
				->withQuery($redirectUrl->getQuery())
				->withFragment($redirectUrl->getFragment())
				->getAbsoluteUrl();
		}
		return new Keycloak($config, ['httpClient' => $guzzleHttp]);
	}


	public function loadConfiguration(): void
	{
		/** @var array<string, mixed> $config */
		$config = (array) $this->config;
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('guzzleHttp'))
			->setFactory(Client::class)
			->setArguments([$config['guzzleHttp']]);

		$builder->addDefinition($this->prefix('sessions'))
			->setFactory(KeycloakSessions::class);

		// The HTTP request is autowired, so the order of extensions does not matter.
		$builder->addDefinition($this->prefix('keycloak'))
			->setFactory([self::class, 'createKeycloak'], [
				'config' => $config,
				'guzzleHttp' => '@' . $this->prefix('guzzleHttp'),
			]);
	}
}
