<?php

declare(strict_types=1);

namespace Drago\Keycloak;

use League\OAuth2\Client\Token\AccessToken;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Throwable;


/**
 * Signs in through Keycloak on presenter startup. The backlink of the first request is kept in
 * KeycloakSessions (getItems()->backlink), so the presenter needs no persistent parameter for it.
 */
trait KeycloakAdapter
{
	public function injectKeycloakAuth(
		Presenter $presenter,
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

				return;
			}

			if (empty($state) || ($state !== $keycloakSessions->getItems()->state)) {
				$keycloakSessions->removeAuthState();
				$presenter->error('Invalid state, make sure HTTP sessions are enabled.', 403);
			}

			// The state is valid for a single exchange only.
			$keycloakSessions->removeAuthState();

			// Details of provider errors stay in the exception chain for logs; users get a generic message.
			try {
				$token = $keycloak->getAccessToken('authorizationCode', [
					'code' => $code,
				]);
			} catch (Throwable $e) {
				throw new BadRequestException('Failed to get access token.', 403, $e);
			}

			$keycloakSessions->addAccessToken($token);

			if ($token instanceof AccessToken) {
				try {
					$resourceOwner = $keycloak->getResourceOwner($token);
				} catch (Throwable $e) {
					throw new BadRequestException('Failed to get resource owner.', 403, $e);
				}

				$keycloakSessions->addResourceOwner($resourceOwner);
			}
		};
	}
}
