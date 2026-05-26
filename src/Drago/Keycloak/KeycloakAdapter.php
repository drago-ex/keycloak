<?php

declare(strict_types=1);

namespace Drago\Keycloak;

use League\OAuth2\Client\Token\AccessToken;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Throwable;


/** Trait for integrating Keycloak authentication into Nette presenters. */
trait KeycloakAdapter
{
	/** Injects the Keycloak authentication flow into the presenter startup. */
	public function injectKeycloakAuth(
		BasePresenter $presenter,
		Keycloak $keycloak,
		KeycloakSessions $keycloakSessions,
	): void
	{
		$presenter->onStartup[] = function () use ($presenter, $keycloak, $keycloakSessions) {
			[$state, $code, $backlink] = array_map(
				fn($i) => $presenter->getParameter($i),
				['state', 'code', 'backlink'],
			);

			$user = $presenter->getUser();
			if (!isset($code)) {
				if (!$user->isLoggedIn()) {
					$authUrl = $keycloak->getAuthorizationUrl();
					$keycloakSessions->addAuthState($keycloak->getState(), is_string($backlink) ? $backlink : null);
					$presenter->redirectUrl($authUrl);
				}

			} elseif (empty($state) || ($state !== $keycloakSessions->getItems()->state)) {
				$keycloakSessions->removeAuthState();
				$presenter->error(
					'Invalid state, make sure HTTP sessions are enabled.',
					403,
				);

			} else {
				$token = null;

				try {
					$token = $keycloak->getAccessToken('authorizationCode', [
						'code' => $code,
					]);
					$keycloakSessions->addAccessToken($token);
					$backlinkValue = $keycloakSessions->getItems()->backlink ?? '';
					$presenter->backlink = $backlinkValue;

				} catch (Throwable $e) {
					$presenter->error(
						'Failed to get access token: ' . $e->getMessage(),
						403,
					);
				}

				if ($token instanceof AccessToken) {
					try {
						$resourceOwner = $keycloak->getResourceOwner($token);
						$keycloakSessions->addResourceOwner($resourceOwner);

					} catch (Throwable $e) {
						$presenter->error(
							'Failed to get resource owner: ' . $e->getMessage(),
							403,
						);
					}
				}
			}
		};
	}
}
