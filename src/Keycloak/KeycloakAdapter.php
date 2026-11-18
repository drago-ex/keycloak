<?php declare(strict_types=1);

namespace Drago\Keycloak;

use League\OAuth2\Client\Token\AccessToken;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Throwable;
use function is_string;


/**
 * Signs in through Keycloak on presenter startup. The backlink of the first request is kept in
 * KeycloakSessions (getItems()->backlink), so the presenter needs no persistent parameter for it.
 * The authorization code is protected by PKCE (S256).
 *
 * The handler is put first in onStartup, before the handlers registered earlier (e.g. an ACL check
 * of a parent presenter, whose inject methods run first), so a guest reaches Keycloak and the code
 * is exchanged before anything else may send the guest away.
 */
trait KeycloakAdapter
{
	public function injectKeycloakAuth(
		Presenter $presenter,
		Keycloak $keycloak,
		KeycloakSessions $keycloakSessions,
	): void
	{
		array_unshift($presenter->onStartup, function () use ($presenter, $keycloak, $keycloakSessions) {
			[$state, $code, $backlink] = array_map(
				fn($i) => $presenter->getParameter($i),
				['state', 'code', 'backlink'],
			);

			$user = $presenter->getUser();
			if (!isset($code)) {
				if (!$user->isLoggedIn()) {
					$pkceCode = Pkce::createVerifier();
					$authUrl = $keycloak->getAuthorizationUrl([
						'code_challenge' => Pkce::challenge($pkceCode),
						'code_challenge_method' => Pkce::Method,
					]);
					$keycloakSessions->addAuthState($keycloak->getState(), is_string($backlink) ? $backlink : null, $pkceCode);
					$presenter->redirectUrl($authUrl);
				}

				return;
			}

			if (empty($state) || ($state !== $keycloakSessions->getItems()->state)) {
				$keycloakSessions->removeAuthState();
				$presenter->error('Invalid state, make sure HTTP sessions are enabled.', 403);
			}

			// The state and the PKCE code are valid for a single exchange only. PKCE is always on: a sign-in
			// without the verifier is refused, it is never exchanged without it.
			$pkceCode = $keycloakSessions->getItems()->pkceCode;
			$keycloakSessions->removeAuthState();
			if ($pkceCode === null || $pkceCode === '') {
				$presenter->error('Missing PKCE verifier, make sure HTTP sessions are enabled.', 403);
			}

			// Details of provider errors stay in the exception chain for logs; users get a generic message.
			// The verifier is removed from the shared provider afterwards, so no later request sends it
			// (an empty one is not sent, the provider checks !empty).
			$keycloak->setPkceCode($pkceCode);
			try {
				$token = $keycloak->getAccessToken('authorizationCode', [
					'code' => $code,
				]);
			} catch (Throwable $e) {
				throw new BadRequestException('Failed to get access token.', 403, $e);
			} finally {
				$keycloak->setPkceCode('');
			}

			$keycloakSessions->addAccessToken($token);

			if ($token instanceof AccessToken) {
				try {
					$resourceOwner = $keycloak->getResourceOwner($token);
				} catch (Throwable $e) {
					throw new BadRequestException('Failed to get resource owner.', 403, $e);
				}

				$keycloakSessions->addResourceOwner($resourceOwner);
				$this->keycloakSignIn($resourceOwner);
			}
		});
	}


	/**
	 * Called right after Keycloak returned the user, before the other startup handlers (e.g. an ACL check).
	 * Override it to sign the user in to the application; by default it does nothing and the presenter
	 * may sign the user in later, e.g. in startup() from KeycloakSessions.
	 */
	protected function keycloakSignIn(KeycloakResourceOwner $resourceOwner): void
	{
	}
}
