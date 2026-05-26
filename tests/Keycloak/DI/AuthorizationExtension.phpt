<?php

/**
 * Test: Drago\Keycloak\DI\KeycloakExtension
 */

declare(strict_types=1);

use Drago\Keycloak\DI\KeycloakExtension;
use Drago\Keycloak\KeycloakSessions;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';


class TestKeycloakExtension extends TestCase
{
	private function createContainer(): Container
	{
		$loader = new ContainerLoader(TempDir, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->loadConfig(Tester\FileMock::create('
			keycloak:
				authServerUrl: keycloak-server-url
				realm: keycloak-realm
				clientId: keycloak-client-id
				clientSecret: keycloak-client-secret
				redirectUri: https://example.com/callback-url

				# optional
				version: 21.0.1
				encryptionAlgorithm: RS256
				encryptionKeyPath: ../key.pem
				encryptionKey: contents_of_key_or_certificate
				guzzleHttp:

			services:
				- Nette\Http\Request
				- Nette\Http\UrlScript
				- Nette\Http\Response
				- Nette\Http\Session
			', 'neon'));
			$compiler->addExtension('keycloak', new KeycloakExtension);
		});
		return new $class;
	}


	private function getClassByType(): KeycloakSessions
	{
		return $this->createContainer()
			->getByType(KeycloakSessions::class);
	}


	public function test01(): void
	{
		Assert::type(KeycloakSessions::class, $this->getClassByType());
	}
}

(new TestKeycloakExtension)->run();
