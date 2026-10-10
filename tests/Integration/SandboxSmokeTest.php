<?php

namespace PontoConnect\Tests\Integration;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use PontoConnect\Api\PontoConnectApiClient;
use PontoConnect\Api\Requests\FinancialInstitution\GetFinancialInstitutionsRequest;
use PontoConnect\Api\Requests\OAuth\RequestClientAccessTokenRequest;
use PontoConnect\Api\Requests\Webhook\GetWebhookKeysRequest;
use PontoConnect\Configuration;
use PontoConnect\Data\FinancialInstitution;
use PontoConnect\Data\Page;
use PontoConnect\Data\Token;
use PontoConnect\Support\Pkce;

/**
 * Calls the real Ponto Connect sandbox over mutual TLS.
 *
 * Not part of the default test suite. Skipped unless these environment
 * variables are set: PONTO_CONNECT_CLIENT_ID, PONTO_CONNECT_CLIENT_SECRET,
 * PONTO_CONNECT_CERTIFICATE_PATH and PONTO_CONNECT_PRIVATE_KEY_PATH
 * (plus PONTO_CONNECT_PRIVATE_KEY_PASSPHRASE when the key is encrypted).
 *
 * Set PONTO_CONNECT_TEST_REDIRECT_URI to one of the redirect URIs registered
 * for the application to also check that the authorization server accepts
 * the configured scopes.
 *
 * Only status codes are written to the output, never tokens or secrets.
 */
class SandboxSmokeTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $requiredVariables = [
            'PONTO_CONNECT_CLIENT_ID',
            'PONTO_CONNECT_CLIENT_SECRET',
            'PONTO_CONNECT_CERTIFICATE_PATH',
            'PONTO_CONNECT_PRIVATE_KEY_PATH',
        ];

        foreach ($requiredVariables as $variable) {
            if ($this->environmentValue($variable) === null) {
                $this->markTestSkipped(sprintf('Set %s to run the Ponto Connect sandbox smoke test.', $variable));
            }
        }

        $this->configuration = Configuration::fromArray([
            'environment' => $this->environmentValue('PONTO_CONNECT_ENVIRONMENT') ?? 'sandbox',
            'client_id' => $this->environmentValue('PONTO_CONNECT_CLIENT_ID'),
            'client_secret' => $this->environmentValue('PONTO_CONNECT_CLIENT_SECRET'),
            'application_id' => $this->environmentValue('PONTO_CONNECT_APPLICATION_ID'),
            'certificate_path' => $this->environmentValue('PONTO_CONNECT_CERTIFICATE_PATH'),
            'private_key_path' => $this->environmentValue('PONTO_CONNECT_PRIVATE_KEY_PATH'),
            'private_key_passphrase' => $this->environmentValue('PONTO_CONNECT_PRIVATE_KEY_PASSPHRASE'),
            'ca_chain_path' => $this->environmentValue('PONTO_CONNECT_CA_CHAIN_PATH'),
            'api_url' => $this->environmentValue('PONTO_CONNECT_API_URL'),
            'scopes' => $this->environmentValue('PONTO_CONNECT_SCOPES'),
        ]);

        if (! $this->configuration->isSandbox()) {
            $this->markTestSkipped('The smoke test only runs against the sandbox.');
        }
    }

    public function test_client_credentials_token_and_financial_institutions(): void
    {
        $client = new PontoConnectApiClient($this->configuration);

        $tokenResponse = $client->send(new RequestClientAccessTokenRequest(
            $this->configuration->clientId,
            $this->configuration->clientSecret,
        ));
        $this->report('POST /ponto-connect/oauth2/token (client_credentials)', $tokenResponse->status());

        $token = Token::fromArray($tokenResponse->json());
        $this->assertNotSame('', $token->accessToken);
        $this->assertGreaterThan(0, $token->expiresIn);

        $institutionsResponse = $client->send(new GetFinancialInstitutionsRequest(limit: 5));
        $this->report('GET /ponto-connect/financial-institutions', $institutionsResponse->status());

        $page = Page::fromResponse(
            $institutionsResponse->json(),
            fn (array $resource) => FinancialInstitution::fromArray($resource),
        );
        $this->assertNotEmpty($page->items);
        $this->assertNotSame('', $page->items[0]->id);
    }

    public function test_webhook_keys_are_available(): void
    {
        $client = new PontoConnectApiClient($this->configuration);

        $response = $client->send(new GetWebhookKeysRequest());
        $this->report('GET /webhooks/keys', $response->status());

        $this->assertNotEmpty($response->json('keys'));
    }

    public function test_authorization_server_accepts_the_configured_scopes(): void
    {
        $redirectUri = $this->environmentValue('PONTO_CONNECT_TEST_REDIRECT_URI');
        if ($redirectUri === null) {
            $this->markTestSkipped('Set PONTO_CONNECT_TEST_REDIRECT_URI to probe the authorization URL.');
        }

        $client = new PontoConnectApiClient($this->configuration);
        $authorizationUrl = $client->oauth->authorizationUrl(
            redirectUri: $redirectUri,
            state: bin2hex(random_bytes(16)),
            codeChallenge: Pkce::challenge(Pkce::generateVerifier()),
        );

        $response = (new Client())->get($authorizationUrl, [
            'allow_redirects' => false,
            'http_errors' => false,
            'timeout' => $this->configuration->timeout,
        ]);

        $location = $response->getHeaderLine('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $redirectQuery);
        $error = $redirectQuery['error'] ?? null;

        $this->report(
            sprintf('GET /oauth2/auth (scope "%s")', implode(' ', $this->configuration->scopes)),
            $response->getStatusCode(),
            is_string($error) ? 'error='.$error : 'no error in redirect',
        );

        $this->assertLessThan(400, $response->getStatusCode());
        $this->assertNull($error, 'The authorization server rejected the request.');
    }

    private function report(string $call, int $status, string $detail = ''): void
    {
        fwrite(STDERR, trim(sprintf('%s -> %d %s', $call, $status, $detail)).PHP_EOL);
    }

    private function environmentValue(string $name): ?string
    {
        $value = getenv($name);
        if ($value === false || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
