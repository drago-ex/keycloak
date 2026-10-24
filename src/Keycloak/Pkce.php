<?php

declare(strict_types=1);

namespace Drago\Keycloak;


/**
 * Proof Key for Code Exchange (RFC 7636, method S256): the code verifier stays in the session
 * and only its hash goes to Keycloak, so a stolen authorization code cannot be exchanged.
 */
final class Pkce
{
	public const string Method = 'S256';


	/** Random code verifier, 64 characters (RFC 7636 allows 43 to 128). */
	public static function createVerifier(): string
	{
		return bin2hex(random_bytes(32));
	}


	/** Code challenge of the verifier: base64url of its SHA-256 hash, without padding. */
	public static function challenge(string $verifier): string
	{
		return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
	}
}
