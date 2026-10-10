<?php

namespace PontoConnect\Tests\Unit;

use InvalidArgumentException;
use PontoConnect\Api\PontoConnectApiClient;
use PontoConnect\Configuration;
use PontoConnect\Enums\Environment;
use PontoConnect\Facades\PontoConnect;
use PontoConnect\PontoConnectManager;
use PontoConnect\Tests\TestCase;
use PontoConnect\Webhooks\WebhookVerifier;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class PontoConnectManagerTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('ponto-connect', [
            'environment' => 'live',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'application_id' => '',
            'certificate_path' => '/secure/ponto/certificate.pem',
            'private_key_path' => '/secure/ponto/private_key.pem',
            'private_key_passphrase' => 'key-passphrase',
            'ca_chain_path' => '',
            'api_url' => 'https://api.ibanity.com',
            'scopes' => 'ai, name offline_access',
            'timeout' => '15',
        ]);
    }

    public function test_configuration_is_built_from_the_config_file(): void
    {
        $configuration = PontoConnect::configuration();

        $this->assertSame(Environment::LIVE, $configuration->environment);
        $this->assertSame('client-id', $configuration->clientId);
        $this->assertSame('client-id', $configuration->applicationId);
        $this->assertSame('https://authorization.myponto.com', $configuration->authorizationUrl);
        $this->assertNull($configuration->caChainPath);
        $this->assertSame(['ai', 'name', 'offline_access'], $configuration->scopes);
        $this->assertSame(15, $configuration->timeout);
    }

    public function test_configuration_defaults(): void
    {
        $configuration = Configuration::fromArray([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'application_id' => 'application-id',
        ]);

        $this->assertSame(Environment::SANDBOX, $configuration->environment);
        $this->assertSame('application-id', $configuration->applicationId);
        $this->assertSame('https://sandbox-authorization.myponto.com', $configuration->authorizationUrl);
        $this->assertSame(['ai', 'pi', 'name', 'offline_access'], $configuration->scopes);
        $this->assertSame('https://api.ibanity.com', $configuration->apiUrl);
        $this->assertSame(30, $configuration->timeout);
    }

    public function test_invalid_environment_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Configuration::fromArray(['environment' => 'production']);
    }

    public function test_configuration_debug_output_hides_secrets(): void
    {
        $debugOutput = print_r(PontoConnect::configuration(), true);

        $this->assertStringNotContainsString('client-secret', $debugOutput);
        $this->assertStringNotContainsString('key-passphrase', $debugOutput);
    }

    public function test_connector_uses_mutual_tls_on_every_request(): void
    {
        $client = PontoConnect::api();

        $this->assertSame([
            'timeout' => 15,
            'verify' => true,
            'cert' => ['/secure/ponto/certificate.pem', 'key-passphrase'],
            'ssl_key' => ['/secure/ponto/private_key.pem', 'key-passphrase'],
        ], $client->config()->all());
        $this->assertSame('https://api.ibanity.com', $client->resolveBaseUrl());
    }

    public function test_connector_verifies_the_server_with_the_ca_chain_when_configured(): void
    {
        $configuration = Configuration::fromArray([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'certificate_path' => '/secure/ponto/certificate.pem',
            'private_key_path' => '/secure/ponto/private_key.pem',
            'ca_chain_path' => '/secure/ponto/ca_chain.pem',
        ]);

        $client = new PontoConnectApiClient($configuration);

        $this->assertSame([
            'timeout' => 30,
            'verify' => '/secure/ponto/ca_chain.pem',
            'cert' => '/secure/ponto/certificate.pem',
            'ssl_key' => '/secure/ponto/private_key.pem',
        ], $client->config()->all());
    }

    public function test_api_returns_a_fresh_connector_per_token(): void
    {
        $manager = app(PontoConnectManager::class);

        $clientWithoutToken = $manager->api();
        $clientWithToken = $manager->withAccessToken('customer-token');

        $this->assertInstanceOf(PontoConnectApiClient::class, $clientWithToken);
        $this->assertNotSame($clientWithoutToken, $clientWithToken);
        $this->assertNotSame($manager->api(), $manager->api());
    }

    public function test_with_access_token_sends_a_bearer_token(): void
    {
        $mockClient = new MockClient([MockResponse::make(['sub' => 'organization-id'])]);
        $client = PontoConnect::withAccessToken('customer-token');
        $client->withMockClient($mockClient);

        $client->userInfo->get();

        $this->assertSame('Bearer customer-token', $this->lastRequest($mockClient)->headers()->get('Authorization'));
    }

    public function test_authorization_url_helper_uses_the_live_host_and_configured_scopes(): void
    {
        $url = PontoConnect::authorizationUrl('https://app.chuck.be/ponto/callback', 'state', 'challenge');

        $this->assertStringStartsWith('https://authorization.myponto.com/oauth2/auth?client_id=client-id&', $url);
        $this->assertStringContainsString('scope=ai%20name%20offline_access', $url);
    }

    public function test_webhook_verifier_uses_the_application_id(): void
    {
        $this->assertInstanceOf(WebhookVerifier::class, PontoConnect::webhookVerifier());
    }

    public function test_manager_is_a_singleton(): void
    {
        $this->assertSame(app(PontoConnectManager::class), app('ponto-connect'));
        $this->assertInstanceOf(PontoConnectManager::class, PontoConnect::getFacadeRoot());
    }
}
