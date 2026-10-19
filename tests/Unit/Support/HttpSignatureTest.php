<?php

namespace PontoConnect\Tests\Unit\Support;

use GuzzleHttp\Psr7\Request;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;
use PontoConnect\Api\PontoConnectApiClient;
use PontoConnect\Api\Requests\Account\GetAccountsRequest;
use PontoConnect\Configuration;
use PontoConnect\Enums\Environment;
use PontoConnect\Support\HttpSignature;
use PontoConnect\Tests\TestCase;

class HttpSignatureTest extends TestCase
{
    private const CERTIFICATE_ID = '75dd1f4a-7b4a-4a56-8b89-b6b2e9b2d5a1';

    private const CREATED_AT = 1760900000;

    private static ?PrivateKey $privateKey = null;

    public function test_the_digest_is_the_url_safe_base64_sha512_of_the_body(): void
    {
        $signature = new HttpSignature(self::CERTIFICATE_ID, $this->privateKey()->toString('PKCS8'));

        $this->assertSame(
            'SHA-512=z4PhNX7vuL3xVChQ1m2AB9Yg5AULVxXcg_SpIdNs6c5H0NE8XYXysP-DGNKHfuwvY7kxvUdBeoGlODJ6-SfaPg==',
            $signature->digest(''),
        );
    }

    public function test_a_request_is_signed_over_its_target_host_digest_creation_time_authorization_and_ibanity_headers(): void
    {
        $body = '{"data":{"type":"reauthorizationRequest"}}';
        $request = new Request('POST', 'https://api.ibanity.com/ponto-connect/accounts/123/reauthorization-requests?page%5Blimit%5D=10', [
            'Authorization' => 'Bearer customer-access-token',
            'Ibanity-Idempotency-Key' => 'e1b4e7b0-0f26-4c2e-a2b4-3f0e1a5f6c11',
            'Accept' => 'application/vnd.api+json',
        ], $body);

        $signed = (new HttpSignature(self::CERTIFICATE_ID, $this->privateKey()->toString('PKCS8')))->sign($request, self::CREATED_AT);

        $digest = 'SHA-512='.strtr(base64_encode(hash('sha512', $body, true)), '+/', '-_');
        $this->assertSame($digest, $signed->getHeaderLine('Digest'));

        $fields = $this->signatureFields($signed->getHeaderLine('Signature'));
        $this->assertSame(self::CERTIFICATE_ID, $fields['keyId']);
        $this->assertSame((string) self::CREATED_AT, $fields['created']);
        $this->assertSame('hs2019', $fields['algorithm']);
        $this->assertSame('(request-target) host digest (created) authorization ibanity-idempotency-key', $fields['headers']);

        $signingString = implode("\n", [
            '(request-target): post /ponto-connect/accounts/123/reauthorization-requests?page%5Blimit%5D=10',
            'host: api.ibanity.com',
            'digest: '.$digest,
            '(created): '.self::CREATED_AT,
            'authorization: Bearer customer-access-token',
            'ibanity-idempotency-key: e1b4e7b0-0f26-4c2e-a2b4-3f0e1a5f6c11',
        ]);
        $this->assertTrue($this->publicKey()->verify($signingString, base64_decode(strtr($fields['signature'], '-_', '+/'))));
        $this->assertSame($body, (string) $signed->getBody());
    }

    public function test_a_request_without_authorization_or_ibanity_headers_signs_the_four_base_headers(): void
    {
        $signed = (new HttpSignature(self::CERTIFICATE_ID, $this->privateKey()->toString('PKCS8')))
            ->sign(new Request('GET', 'https://api.ibanity.com/webhooks/keys'), self::CREATED_AT);

        $this->assertSame('(request-target) host digest (created)', $this->signatureFields($signed->getHeaderLine('Signature'))['headers']);
    }

    public function test_the_connector_signs_live_requests_once_the_signature_certificate_is_configured(): void
    {
        $privateKeyPath = tempnam(sys_get_temp_dir(), 'ponto-signature-key');
        file_put_contents($privateKeyPath, $this->privateKey()->withPassword('secret')->toString('PKCS8'));

        $client = new PontoConnectApiClient(new Configuration(
            environment: Environment::LIVE,
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            signatureCertificateId: self::CERTIFICATE_ID,
            signaturePrivateKeyPath: $privateKeyPath,
            signaturePrivateKeyPassphrase: 'secret',
        ), 'customer-access-token');

        $psrRequest = $client->createPendingRequest(new GetAccountsRequest())->createPsrRequest();

        unlink($privateKeyPath);
        $this->assertTrue($psrRequest->hasHeader('Digest'));
        $this->assertStringContainsString('keyId="'.self::CERTIFICATE_ID.'"', $psrRequest->getHeaderLine('Signature'));
    }

    public function test_the_sandbox_sends_requests_unsigned(): void
    {
        $client = new PontoConnectApiClient($this->configuration(), 'customer-access-token');

        $psrRequest = $client->createPendingRequest(new GetAccountsRequest())->createPsrRequest();

        $this->assertFalse($psrRequest->hasHeader('Digest'));
        $this->assertFalse($psrRequest->hasHeader('Signature'));
    }

    public function test_the_signature_settings_come_from_the_config_and_the_passphrase_is_hidden(): void
    {
        $configuration = Configuration::fromArray([
            'environment' => 'live',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'signature_certificate_id' => self::CERTIFICATE_ID,
            'signature_private_key_path' => '/secure/ponto/signature_private_key.pem',
            'signature_private_key_passphrase' => 'secret',
        ]);

        $this->assertTrue($configuration->signsRequests());
        $this->assertSame('********', $configuration->__debugInfo()['signaturePrivateKeyPassphrase']);
        $this->assertFalse(Configuration::fromArray(['client_id' => self::CLIENT_ID, 'signature_certificate_id' => ''])->signsRequests());
    }

    /**
     * @return array<string, string>
     */
    private function signatureFields(string $signatureHeader): array
    {
        preg_match_all('/(\w+)="([^"]*)"/', $signatureHeader, $matches, PREG_SET_ORDER);

        $fields = [];
        foreach ($matches as $match) {
            $fields[$match[1]] = $match[2];
        }

        return $fields;
    }

    private function privateKey(): PrivateKey
    {
        return self::$privateKey ??= RSA::createKey(2048);
    }

    private function publicKey(): PublicKey
    {
        return $this->privateKey()->getPublicKey()
            ->withPadding(RSA::SIGNATURE_PSS)
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->withSaltLength(32);
    }
}
