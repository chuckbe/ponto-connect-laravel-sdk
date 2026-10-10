<?php

namespace PontoConnect\Tests\Unit\Resources;

use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

class IntegrationResourceTest extends TestCase
{
    public function test_delete_revokes_the_organization_integration(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('integration-deleted')]);
        $client = $this->mockedClient($mockClient, 'client-access-token');

        $integration = $client->integration->delete('3953d4ab-7ec4-4c0b-8b91-4be8626c0946');

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('DELETE', $pendingRequest->getMethod()->value);
        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/organizations/3953d4ab-7ec4-4c0b-8b91-4be8626c0946/integration',
            $pendingRequest->getUrl(),
        );
        $this->assertSame('e1848b9c-9c72-431e-b0c0-410b0e579166', $integration->id);
        $this->assertSame('3953d4ab-7ec4-4c0b-8b91-4be8626c0946', $integration->organizationId);
    }
}
