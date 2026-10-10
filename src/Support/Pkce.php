<?php

namespace PontoConnect\Support;

use InvalidArgumentException;

/**
 * Proof Key for Code Exchange (RFC 7636) helpers for the authorization flow.
 *
 * Store the verifier in the session, send the challenge in the authorization
 * URL and send the verifier again when exchanging the authorization code.
 */
final class Pkce
{
    public const MINIMUM_VERIFIER_LENGTH = 43;

    public const MAXIMUM_VERIFIER_LENGTH = 128;

    private const UNRESERVED_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';

    public static function generateVerifier(int $length = 64): string
    {
        if ($length < self::MINIMUM_VERIFIER_LENGTH || $length > self::MAXIMUM_VERIFIER_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'A PKCE code verifier must be between %d and %d characters long.',
                self::MINIMUM_VERIFIER_LENGTH,
                self::MAXIMUM_VERIFIER_LENGTH,
            ));
        }

        $lastCharacterIndex = strlen(self::UNRESERVED_CHARACTERS) - 1;

        $verifier = '';
        for ($position = 0; $position < $length; $position++) {
            $verifier .= self::UNRESERVED_CHARACTERS[random_int(0, $lastCharacterIndex)];
        }

        return $verifier;
    }

    /**
     * The S256 challenge: base64url(SHA-256(verifier)) without padding.
     */
    public static function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * @return array{verifier: string, challenge: string}
     */
    public static function generate(int $verifierLength = 64): array
    {
        $verifier = self::generateVerifier($verifierLength);

        return [
            'verifier' => $verifier,
            'challenge' => self::challenge($verifier),
        ];
    }
}
