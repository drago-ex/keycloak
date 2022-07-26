<?php

declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Keycloak;

use Exception;
use Nette\Application\UI\Presenter;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;


trait KeycloakAdapter
{
	public function injectKeycloakAuth(Presenter $presenter, Keycloak $keycloak, KeycloakSessions $keycloakSessions): void
	{
		$presenter->onStartup[] = function () use ($presenter, $keycloak, $keycloakSessions) {
			$state = $presenter->getParameter('state');
			$code = $presenter->getParameter('code');

			if (!isset($code)) {

				// If we don't have an authorization code then get one.
				if (!$presenter->getUser()->isLoggedIn()) {
					$authUrl = $keycloak->getAuthorizationUrl();
					$keycloakSessions->addAuthState($keycloak->getState());
					$presenter->redirectUrl($authUrl);
				}

			// Check given state against previously stored one to mitigate CSRF attack.
			} elseif (empty($state) || ($state !== $keycloakSessions->getItems()->state)) {
				$keycloakSessions->removeAuthState();
				$presenter->error(
					'Invalid state, make sure HTTP sessions are enabled.',
					403,
				);

			} else {

				// Try to get an access token (using the authorization coe grant).
				try {
					$token = $keycloak->getAccessToken('authorizationCode', [
						'code' => $code,
					]);
					$keycloakSessions->addAccessToken($token);

				} catch (Exception $e) {
					$presenter->error(
						'Failed to get access token: ' . $e->getMessage(),
						403,
					);
				}

				// Optional: Now you have a token you can look up a users profile data.
				try {
					// We got an access token, let's now get the user's details.
					$resourceOwner = $keycloak->getResourceOwner($token);
					$keycloakSessions->addResourceOwner($resourceOwner);

				} catch (Exception $e) {
					$presenter->error(
						'Failed to get resource owner: ' . $e->getMessage(),
						403,
					);
				}
			}
		};
	}
}
