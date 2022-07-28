<?php

/**
 * Test: Drago\Keycloak\DI\KeycloakExtension
 */

declare(strict_types=1);


use Drago\Keycloak\DI\KeycloakExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';


class TestKeycloakExtension extends TestCase
{
	protected Container $container;


	public function __construct(Container $container)
	{
		$this->container = $container;
	}


	public function createContainer(): Container
	{
		$loader = new ContainerLoader($this->container->getParameters()['tempDir'], true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->loadConfig(Tester\FileMock::create('
			keycloak:
				authServerUrl: keycloak-server-url
				realm: keycloak-realm
				clientId: keycloak-client-id
				clientSecret: keycloak-client-secret
				redirectUri: https://example.com/callback-url
				# optional
				encryptionAlgorithm: RS256
				encryptionKeyPath: ../key.pem
				encryptionKey: contents_of_key_or_certificate
				guzzleHttp: []
			', 'neon'));
			$compiler->addExtension('generator', new KeycloakExtension);
		});
		return new $class;
	}
}

(new TestKeycloakExtension($container))->run();
