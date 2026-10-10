<?php

namespace PontoConnect\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PontoConnect\Api\PontoConnectApiClient;
use PontoConnect\Configuration;
use PontoConnect\Enums\Environment;
use PontoConnect\Facades\PontoConnect;
use PontoConnect\PontoConnectServiceProvider;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

abstract class TestCase extends OrchestraTestCase
{
    protected const CLIENT_ID = 'b5ddd91d-a600-4268-956f-1dc7023fb6ad';

    protected const CLIENT_SECRET = '2aa72295-6247-4c18-8cb3-05495b50a344';

    protected function getPackageProviders($app): array
    {
        return [
            PontoConnectServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'PontoConnect' => PontoConnect::class,
        ];
    }

    protected function configuration(): Configuration
    {
        return new Configuration(
            environment: Environment::SANDBOX,
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            certificatePath: '/secure/ponto/certificate.pem',
            privateKeyPath: '/secure/ponto/private_key.pem',
            privateKeyPassphrase: 'azertyuiop',
        );
    }

    protected function mockedClient(MockClient $mockClient, ?string $accessToken = 'customer-access-token'): PontoConnectApiClient
    {
        $client = new PontoConnectApiClient($this->configuration(), $accessToken);
        $client->withMockClient($mockClient);

        return $client;
    }

    protected function lastRequest(MockClient $mockClient): PendingRequest
    {
        $pendingRequest = $mockClient->getLastPendingRequest();
        $this->assertInstanceOf(PendingRequest::class, $pendingRequest);

        return $pendingRequest;
    }

    /**
     * @return array<string, mixed>
     */
    protected function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__.'/Fixtures/'.$name.'.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function fixtureResponse(string $name, int $status = 200): MockResponse
    {
        return MockResponse::make($this->fixture($name), $status);
    }
}
