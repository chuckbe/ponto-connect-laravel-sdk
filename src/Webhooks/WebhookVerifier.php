<?php

namespace PontoConnect\Webhooks;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use JsonException;
use PontoConnect\Exceptions\UnknownWebhookKeyException;
use PontoConnect\Exceptions\WebhookVerificationException;
use stdClass;
use Throwable;

/**
 * Verifies the `Signature` header of a Ponto Connect webhook
 * (Webhooks guide, "Security" > "Signature", steps 1 to 4).
 *
 * The header is a JWT signed with RS512 by one of the keys from
 * GET /webhooks/keys. Its claims bind it to your application (`aud`),
 * to Ibanity (`iss`), to a time window (`iat`/`exp`) and to the exact
 * request body (`digest` = base64(SHA-512(raw body))).
 */
class WebhookVerifier
{
    public const ALGORITHM = 'RS512';

    public const ISSUER = 'https://api.ibanity.com';

    public const DEFAULT_LEEWAY_SECONDS = 30;

    public function __construct(
        protected string $applicationId,
        protected int $leewaySeconds = self::DEFAULT_LEEWAY_SECONDS,
    ) {
    }

    /**
     * @param string $signatureHeader the value of the `Signature` request header
     * @param string $rawBody the raw, unparsed request body
     * @param array{keys?: list<array<string, mixed>>} $jwks the key set from GET /webhooks/keys
     *
     * @throws UnknownWebhookKeyException when the key id is not in the key set (refetch the keys and retry once)
     * @throws WebhookVerificationException when the signature or any claim is invalid
     */
    public function verify(string $signatureHeader, string $rawBody, array $jwks): WebhookEvent
    {
        $keyId = $this->signingKeyId($signatureHeader);
        $key = $this->findKey($jwks, $keyId);
        $claims = $this->decode($signatureHeader, $key);

        $this->assertAudience($claims);
        $this->assertIssuer($claims);
        $this->assertTimestamps($claims);
        $this->assertDigest($claims, $rawBody);

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new WebhookVerificationException('The webhook body is not valid JSON.', 0, $exception);
        }

        if (! is_array($payload)) {
            throw new WebhookVerificationException('The webhook body is not a JSON object.');
        }

        $jti = null;
        if (isset($claims->jti) && is_string($claims->jti)) {
            $jti = $claims->jti;
        }

        return WebhookEvent::fromPayload($payload, $jti);
    }

    private function signingKeyId(string $signatureHeader): string
    {
        $segments = explode('.', $signatureHeader);
        if (count($segments) !== 3) {
            throw new WebhookVerificationException('The webhook signature is not a valid JWT.');
        }

        try {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode($segments[0]));
        } catch (Throwable $exception) {
            throw new WebhookVerificationException('The webhook signature header cannot be decoded.', 0, $exception);
        }

        if (! $header instanceof stdClass) {
            throw new WebhookVerificationException('The webhook signature header cannot be decoded.');
        }

        if (($header->alg ?? null) !== self::ALGORITHM) {
            throw new WebhookVerificationException('The webhook signature must use the RS512 algorithm.');
        }

        if (! isset($header->kid) || ! is_string($header->kid) || $header->kid === '') {
            throw new WebhookVerificationException('The webhook signature has no key id.');
        }

        return $header->kid;
    }

    /**
     * @param array{keys?: list<array<string, mixed>>} $jwks
     */
    private function findKey(array $jwks, string $keyId): Key
    {
        foreach ($jwks['keys'] ?? [] as $jwk) {
            if (($jwk['kid'] ?? null) !== $keyId) {
                continue;
            }

            if (isset($jwk['alg']) && $jwk['alg'] !== self::ALGORITHM) {
                throw new WebhookVerificationException(sprintf('Key "%s" is not an RS512 key.', $keyId));
            }

            try {
                $key = JWK::parseKey($jwk, self::ALGORITHM);
            } catch (Throwable $exception) {
                throw new WebhookVerificationException(sprintf('Key "%s" cannot be parsed.', $keyId), 0, $exception);
            }

            if ($key === null) {
                throw new WebhookVerificationException(sprintf('Key "%s" cannot be parsed.', $keyId));
            }

            return new Key($key->getKeyMaterial(), self::ALGORITHM);
        }

        throw new UnknownWebhookKeyException($keyId);
    }

    private function decode(string $signatureHeader, Key $key): stdClass
    {
        $previousLeeway = JWT::$leeway;
        JWT::$leeway = $this->leewaySeconds;

        try {
            return JWT::decode($signatureHeader, $key);
        } catch (Throwable $exception) {
            throw new WebhookVerificationException(
                'The webhook signature is invalid: '.$exception->getMessage(),
                0,
                $exception,
            );
        } finally {
            JWT::$leeway = $previousLeeway;
        }
    }

    private function assertAudience(stdClass $claims): void
    {
        $audiences = $claims->aud ?? [];
        if (! is_array($audiences)) {
            $audiences = [$audiences];
        }

        if (! in_array($this->applicationId, $audiences, true)) {
            throw new WebhookVerificationException('The webhook was not issued for this application (aud).');
        }
    }

    private function assertIssuer(stdClass $claims): void
    {
        if (($claims->iss ?? null) !== self::ISSUER) {
            throw new WebhookVerificationException('The webhook was not issued by Ibanity (iss).');
        }
    }

    /**
     * The JWT library only checks `exp` and `iat` when present; both are required here.
     */
    private function assertTimestamps(stdClass $claims): void
    {
        if (! isset($claims->exp) || ! is_numeric($claims->exp)) {
            throw new WebhookVerificationException('The webhook signature has no expiration time (exp).');
        }

        if (! isset($claims->iat) || ! is_numeric($claims->iat)) {
            throw new WebhookVerificationException('The webhook signature has no issued-at time (iat).');
        }
    }

    private function assertDigest(stdClass $claims, string $rawBody): void
    {
        $expectedDigest = base64_encode(hash('sha512', $rawBody, true));

        if (! isset($claims->digest) || ! is_string($claims->digest) || ! hash_equals($expectedDigest, $claims->digest)) {
            throw new WebhookVerificationException('The webhook body does not match the signed digest.');
        }
    }
}
