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
	public function injectKeycloakAuth(Presenter $presenter, Keycloak $keycloak): void
	{
		$presenter->onStartup[] = function () use ($presenter, $keycloak) {
			$session = $presenter->getSession()->getSection('keycloak');
			$state = $presenter->getParameter('state');

			if (!isset($state)) {

				// If we don't have an authorization code then get one.
				$authUrl = $keycloak->getAuthorizationUrl();
				$session->set('oauth2state', $keycloak->getState());
				$presenter->redirectUrl($authUrl);

			// Check given state against previously stored one to mitigate CSRF attack.
			} elseif (empty($state) || ($state !== $session->get('oauth2state'))) {
				$session->remove('oauth2state');
				$presenter->error('Invalid state, make sure HTTP sessions are enabled.', 403);

			} else {

				// Try to get an access token (using the authorization coe grant).
				try {
					$session->set('token', $keycloak->getAccessToken('authorization_code', [
						'code' => $presenter->getParameter('code'),
					]));

				} catch (Exception $e) {
					$presenter->error(
						'Failed to get access token: ' . $e->getMessage(),
						403
					);
				}

				// Optional: Now you have a token you can look up a users profile data.
				try {
					// We got an access token, let's now get the user's details.
					$session->set('owner', $keycloak->getResourceOwner($session->get('token'))
						->toArray());

				} catch (Exception $e) {
					$presenter->error(
						'Failed to get resource owner: ' . $e->getMessage(),
						403
					);
				}
			}
		};
	}
}
