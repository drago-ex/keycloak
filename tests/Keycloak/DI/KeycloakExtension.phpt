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
use Nette\Http;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';


function createContainer(string $config): Container
{
	$loader = new ContainerLoader(TempDir, autoRebuild: true);
	$class = $loader->load(function (Compiler $compiler) use ($config): void {
		$services = '
			services:
				- Nette\Http\Request(Nette\Http\UrlScript("https://app.example.com/admin"))
				- Nette\Http\Response
				- Nette\Http\Session
		';
		$compiler->loadConfig(Tester\FileMock::create($services, 'neon'));
		$compiler->loadConfig(Tester\FileMock::create($config, 'neon'));
		$compiler->addExtension('keycloak', new KeycloakExtension);
	}, md5($config));

	return new $class;
}


const Config = '
	keycloak:
		authServerUrl: https://sso.example.com
		realm: realm
		clientId: client
		clientSecret: secret
		redirectUri: https://example.com/callback-url
';


test('The extension registers the sessions and the Keycloak provider', function () {
	$container = createContainer(Config);

	Assert::type(KeycloakSessions::class, $container->getByType(KeycloakSessions::class));
	$keycloak = $container->getByType(Keycloak::class);
	Assert::type(Keycloak::class, $keycloak);
	Assert::contains('redirect_uri=' . urlencode('https://example.com/callback-url'), $keycloak->getAuthorizationUrl());
});


test('A relative redirect URI is completed with the host of the current request', function () {
	$keycloak = KeycloakExtension::createKeycloak(
		new Http\Request(new Http\UrlScript('https://app.example.com/admin')),
		['authServerUrl' => 'https://sso.example.com', 'realm' => 'realm', 'clientId' => 'client', 'clientSecret' => 'secret', 'redirectUri' => '/sign/in?x=1'],
		new GuzzleHttp\Client,
	);

	Assert::contains('redirect_uri=' . urlencode('https://app.example.com/sign/in?x=1'), $keycloak->getAuthorizationUrl());
});


test('A public client works without a secret and sends no client_secret', function () {
	$container = createContainer('
		keycloak:
			authServerUrl: https://sso.example.com
			realm: realm
			clientId: client
			redirectUri: https://example.com/callback-url
	');
	Assert::type(Keycloak::class, $container->getByType(Keycloak::class));

	$history = [];
	$handler = GuzzleHttp\HandlerStack::create(new GuzzleHttp\Handler\MockHandler([
		new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"access_token":"t","token_type":"bearer"}'),
	]));
	$handler->push(GuzzleHttp\Middleware::history($history));

	$keycloak = KeycloakExtension::createKeycloak(
		new Http\Request(new Http\UrlScript('https://app.example.com/admin')),
		['authServerUrl' => 'https://sso.example.com', 'realm' => 'realm', 'clientId' => 'client', 'clientSecret' => null, 'redirectUri' => '/callback'],
		new GuzzleHttp\Client(['handler' => $handler]),
	);
	$keycloak->getAccessToken('authorization_code', ['code' => 'c1']);

	parse_str((string) $history[0]['request']->getBody(), $body);
	Assert::same('client', $body['client_id']);
	Assert::false(isset($body['client_secret']));
});


test('Required options must be configured', function () {
	Assert::exception(
		fn() => createContainer('
			keycloak:
				authServerUrl: https://sso.example.com
				realm: realm
		'),
		Nette\DI\InvalidConfigurationException::class,
		"The mandatory item 'keycloak%a%clientId' is missing.",
	);
});
