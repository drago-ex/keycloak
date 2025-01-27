<?php

declare(strict_types=1);

/**
 * Drago Extension
 * Package built on Nette Framework
 */

namespace Drago\Keycloak;

use Nette\Application\UI\Presenter;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Throwable;


/**
 * Trait for integrating Keycloak authentication into Nette presenters.
 * This trait manages the Keycloak authentication flow within the presenter.
 */
trait KeycloakAdapter
{
	/**
	 * Injects the Keycloak authentication process into the presenter.
	 * Handles the OAuth2 authorization flow, including redirecting to Keycloak for authorization,
	 * validating the authorization state, retrieving the access token, and storing the resource owner.
	 *
	 * @param Presenter $presenter The Nette presenter where the authentication is injected.
	 * @param Keycloak $keycloak The Keycloak OAuth2 provider instance for authorization.
	 * @param KeycloakSessions $keycloakSessions The session handler for storing OAuth2 data.
	 */
	public function injectKeycloakAuth(
		Presenter $presenter,
		Keycloak $keycloak,
		KeycloakSessions $keycloakSessions,
	): void
	{
		$presenter->onStartup[] = function () use ($presenter, $keycloak, $keycloakSessions) {
			// Fetch the state, code, and backlink parameters from the presenter.
			[$state, $code, $backlink] = array_map(fn($i) => $presenter->getParameter($i), ['state', 'code', 'backlink']);

			// If no authorization code is present, initiate the authorization flow.
			if (!isset($code)) {
				// If the user is not logged in, redirect to Keycloak for authentication.
				if (!$presenter->getUser()->isLoggedIn()) {
					$authUrl = $keycloak->getAuthorizationUrl();
					$keycloakSessions->addAuthState($keycloak->getState(), $backlink);
					$presenter->redirectUrl($authUrl);
				}
			} elseif (empty($state) || ($state !== $keycloakSessions->getItems()->state)) {
				// CSRF protection: Validate that the state parameter matches the stored state.
				$keycloakSessions->removeAuthState();
				$presenter->error(
					'Invalid state, make sure HTTP sessions are enabled.',
					403,
				);
			} else {
				// Attempt to get an access token using the authorization code.
				try {
					$token = $keycloak->getAccessToken('authorizationCode', [
						'code' => $code,
					]);
					$keycloakSessions->addAccessToken($token);
					$backlink = $keycloakSessions->getItems()->backlink ?? '';
					$presenter->backlink = $backlink;
				} catch (Throwable $e) {
					// Handle errors when retrieving the access token.
					$presenter->error(
						'Failed to get access token: ' . $e->getMessage(),
						403,
					);
				}

				// Retrieve the user's profile data using the access token.
				try {
					$resourceOwner = $keycloak->getResourceOwner($token);
					$keycloakSessions->addResourceOwner($resourceOwner);
				} catch (Throwable $e) {
					// Handle errors when retrieving the resource owner.
					$presenter->error(
						'Failed to get resource owner: ' . $e->getMessage(),
						403,
					);
				}
			}
		};
	}
}
