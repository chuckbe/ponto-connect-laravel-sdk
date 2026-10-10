<?php

namespace PontoConnect\Tests\Unit\Resources;

use DateTimeImmutable;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

class IntegrationAccountResourceTest extends TestCase
{
    public function test_list_returns_integration_accounts(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('integration-accounts')]);
        $client = $this->mockedClient($mockClient, 'client-access-token');

        $page = $client->integrationAccount->list(limit: 1);

        $this->assertSame('https://api.ibanity.com/ponto-connect/integration-accounts', $this->lastRequest($mockClient)->getUrl());

        $integrationAccount = $page->items[0];
        $this->assertSame('10fd3e13-4356-4c43-98c1-e7d5543aeee8', $integrationAccount->id);
        $this->assertEquals(new DateTimeImmutable('2024-07-01T09:27:00.741552Z'), $integrationAccount->createdAt);
        $this->assertNull($integrationAccount->lastAccessedAt);
        $this->assertNull($integrationAccount->partnerReference);
        $this->assertSame('37835bf6-c1e0-4dcb-aaba-7e244c756e10', $integrationAccount->organizationId);
        $this->assertSame('b0bb95ff-5b95-441f-a864-0fb0ccea955a', $integrationAccount->accountId);
        $this->assertSame('41ed5259-9e07-43a7-b8c2-3854adef2067', $integrationAccount->financialInstitutionId);
        $this->assertSame('10fd3e13-4356-4c43-98c1-e7d5543aeee8', $page->nextCursor);
    }
}
