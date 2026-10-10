<?php

namespace PontoConnect\Tests\Unit\Webhooks;

use Firebase\JWT\JWT;
use OpenSSLAsymmetricKey;
use PHPUnit\Framework\TestCase;
use PontoConnect\Enums\WebhookEventType;
use PontoConnect\Exceptions\UnknownWebhookKeyException;
use PontoConnect\Exceptions\WebhookVerificationException;
use PontoConnect\Webhooks\WebhookVerifier;

class WebhookVerifierTest extends TestCase
{
    private const APPLICATION_ID = 'ff19beb0-1e67-42e7-9117-8b3635d37d7a';

    private const KEY_ID = 'signing_key_1';

    private static OpenSSLAsymmetricKey $privateKey;

    private static OpenSSLAsymmetricKey $otherPrivateKey;

    /**
     * @var array{keys: list<array<string, string>>}
     */
    private static array $jwks;

    public static function setUpBeforeClass(): void
    {
        self::$privateKey = self::generatePrivateKey();
        self::$otherPrivateKey = self::generatePrivateKey();

        $details = openssl_pkey_get_details(self::$privateKey);
        self::assertIsArray($details);

        self::$jwks = [
            'keys' => [
                [
                    'alg' => 'RS512',
                    'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
                    'kid' => self::KEY_ID,
                    'kty' => 'RSA',
                    'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
                    'status' => 'ACTIVE',
                    'use' => 'sig',
                ],
            ],
        ];
    }

    public function test_valid_signature_returns_the_event(): void
    {
        $rawBody = $this->webhookBody();
        $signature = $this->sign($this->claims($rawBody));

        $event = $this->verifier()->verify($signature, $rawBody, self::$jwks);

        $this->assertSame('3c1e3b35-6bae-4102-b98b-986799cb9d1f', $event->id);
        $this->assertSame('pontoConnect.account.transactionsCreated', $event->type);
        $this->assertSame(WebhookEventType::ACCOUNT_TRANSACTIONS_CREATED, $event->eventType());
        $this->assertTrue($event->is(WebhookEventType::ACCOUNT_TRANSACTIONS_CREATED));
        $this->assertSame('2021-11-05T10:53:36.284000Z', $event->createdAt?->format('Y-m-d\TH:i:s.up'));
        $this->assertSame(3, $event->count());
        $this->assertSame('a193e71b-ae2f-43c7-b408-6d465e55e81e', $event->accountId);
        $this->assertSame('11a67820-0712-4c93-90dc-5c0c897d07b8', $event->organizationId);
        $this->assertSame('1baa772f-efef-4419-b574-9892c8485482', $event->synchronizationId);
        $this->assertNull($event->paymentRequestId);
        $this->assertSame('84385909-e8f5-4114-bf78-9e9e398aa792', $event->jti);
    }

    public function test_wrong_audience_is_rejected(): void
    {
        $rawBody = $this->webhookBody();
        $signature = $this->sign(['aud' => 'another-application-id'] + $this->claims($rawBody));

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('(aud)');

        $this->verifier()->verify($signature, $rawBody, self::$jwks);
    }

    public function test_wrong_issuer_is_rejected(): void
    {
        $rawBody = $this->webhookBody();
        $signature = $this->sign(['iss' => 'https://api.ibanity.localhost'] + $this->claims($rawBody));

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('(iss)');

        $this->verifier()->verify($signature, $rawBody, self::$jwks);
    }

    public function test_tampered_body_does_not_match_the_digest(): void
    {
        $rawBody = $this->webhookBody();
        $signature = $this->sign($this->claims($rawBody));
        $tamperedBody = str_replace('"count":3', '"count":4', $rawBody);

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('digest');

        $this->verifier()->verify($signature, $tamperedBody, self::$jwks);
    }

    public function test_expired_signature_is_rejected(): void
    {
        $rawBody = $this->webhookBody();
        $signature = $this->sign(['iat' => time() - 180, 'exp' => time() - 120] + $this->claims($rawBody));

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('Expired');

        $this->verifier()->verify($signature, $rawBody, self::$jwks);
    }

    public function test_signature_expired_within_the_leeway_is_accepted(): void
    {
        $rawBody = $this->webhookBody();
        $signature = $this->sign(['iat' => time() - 70, 'exp' => time() - 10] + $this->claims($rawBody));

        $event = $this->verifier()->verify($signature, $rawBody, self::$jwks);

        $this->assertSame('3c1e3b35-6bae-4102-b98b-986799cb9d1f', $event->id);
        $this->assertSame(0, JWT::$leeway);
    }

    public function test_signature_issued_in_the_future_is_rejected(): void
    {
        $rawBody = $this->webhookBody();
        $signature = $this->sign(['iat' => time() + 300, 'exp' => time() + 360] + $this->claims($rawBody));

        $this->expectException(WebhookVerificationException::class);

        $this->verifier()->verify($signature, $rawBody, self::$jwks);
    }

    public function test_signature_without_expiration_is_rejected(): void
    {
        $rawBody = $this->webhookBody();
        $claims = $this->claims($rawBody);
        unset($claims['exp']);

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('(exp)');

        $this->verifier()->verify($this->sign($claims), $rawBody, self::$jwks);
    }

    public function test_unknown_key_id_asks_for_fresh_keys(): void
    {
        $rawBody = $this->webhookBody();
        $signature = $this->sign($this->claims($rawBody), keyId: 'rotated_key_2');

        try {
            $this->verifier()->verify($signature, $rawBody, self::$jwks);
            $this->fail('Expected an UnknownWebhookKeyException.');
        } catch (UnknownWebhookKeyException $exception) {
            $this->assertSame('rotated_key_2', $exception->keyId);
        }
    }

    public function test_signature_from_another_key_is_rejected(): void
    {
        $rawBody = $this->webhookBody();
        $signature = JWT::encode($this->claims($rawBody), self::$otherPrivateKey, 'RS512', self::KEY_ID);

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('Signature verification failed');

        $this->verifier()->verify($signature, $rawBody, self::$jwks);
    }

    public function test_other_algorithms_are_rejected(): void
    {
        $rawBody = $this->webhookBody();
        $signature = JWT::encode($this->claims($rawBody), self::$privateKey, 'RS256', self::KEY_ID);

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('RS512');

        $this->verifier()->verify($signature, $rawBody, self::$jwks);
    }

    public function test_the_documented_key_format_can_be_parsed(): void
    {
        $documentedKeys = json_decode(
            (string) file_get_contents(__DIR__.'/../../Fixtures/webhook-keys.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $rawBody = $this->webhookBody();
        $signature = $this->sign($this->claims($rawBody), keyId: 'dummy_1');

        $this->expectException(WebhookVerificationException::class);
        $this->expectExceptionMessage('Signature verification failed');

        $this->verifier()->verify($signature, $rawBody, $documentedKeys);
    }

    public function test_malformed_signature_header_is_rejected(): void
    {
        $this->expectException(WebhookVerificationException::class);

        $this->verifier()->verify('not-a-jwt', $this->webhookBody(), self::$jwks);
    }

    private function verifier(): WebhookVerifier
    {
        return new WebhookVerifier(self::APPLICATION_ID);
    }

    private function webhookBody(): string
    {
        $payload = json_decode(
            (string) file_get_contents(__DIR__.'/../../Fixtures/webhook-account-transactions-created.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, int|string>
     */
    private function claims(string $rawBody): array
    {
        return [
            'aud' => self::APPLICATION_ID,
            'digest' => base64_encode(hash('sha512', $rawBody, true)),
            'exp' => time() + 60,
            'iat' => time(),
            'iss' => 'https://api.ibanity.com',
            'jti' => '84385909-e8f5-4114-bf78-9e9e398aa792',
        ];
    }

    /**
     * @param array<string, int|string> $claims
     */
    private function sign(array $claims, string $keyId = self::KEY_ID): string
    {
        return JWT::encode($claims, self::$privateKey, 'RS512', $keyId);
    }

    private static function generatePrivateKey(): OpenSSLAsymmetricKey
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        self::assertInstanceOf(OpenSSLAsymmetricKey::class, $privateKey);

        return $privateKey;
    }
}
