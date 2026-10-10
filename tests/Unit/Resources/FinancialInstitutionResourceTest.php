<?php

namespace PontoConnect\Tests\Unit\Resources;

use PontoConnect\Data\FinancialInstitution;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

class FinancialInstitutionResourceTest extends TestCase
{
    public function test_list_without_token_and_with_filters(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('financial-institutions')]);
        $client = $this->mockedClient($mockClient, null);

        $page = $client->financialInstitution->list(
            limit: 1,
            filters: ['country' => 'BE', 'name' => ['like' => 'meta'], 'paymentsEnabled' => true],
        );

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('https://api.ibanity.com/ponto-connect/financial-institutions', $pendingRequest->getUrl());
        $this->assertNull($pendingRequest->headers()->get('Authorization'));
        $this->assertSame([
            'page[limit]' => 1,
            'filter[country][eq]' => 'BE',
            'filter[name][like]' => 'meta',
            'filter[paymentsEnabled][eq]' => 'true',
        ], $pendingRequest->query()->all());

        $this->assertInstanceOf(FinancialInstitution::class, $page->items[0]);
        $this->assertSame('MetaBank', $page->items[0]->name);
        $this->assertNull($page->items[0]->bic);
        $this->assertSame('75fc04e8-acc3-428b-9e78-8c5a8a8712c3', $page->nextCursor);
    }

    public function test_get_maps_every_documented_attribute(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('financial-institution')]);
        $client = $this->mockedClient($mockClient);

        $institution = $client->financialInstitution->get('41ed5259-9e07-43a7-b8c2-3854adef2067');

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/financial-institutions/41ed5259-9e07-43a7-b8c2-3854adef2067',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertSame('41ed5259-9e07-43a7-b8c2-3854adef2067', $institution->id);
        $this->assertSame('Gringotts Bank', $institution->name);
        $this->assertSame('stable', $institution->status);
        $this->assertFalse($institution->deprecated);
        $this->assertSame('BE', $institution->country);
        $this->assertSame('NBBEBEBB203', $institution->bic);
        $this->assertSame('https://ibanity-production-financial-institution-assets.s3.eu-central-1.amazonaws.com/sandbox.svg', $institution->logoUrl);
        $this->assertSame('Europe/Brussels', $institution->timeZone);
        $this->assertTrue($institution->pendingTransactionsAvailable);
        $this->assertTrue($institution->paymentsEnabled);
        $this->assertTrue($institution->bulkPaymentsEnabled);
        $this->assertTrue($institution->periodicPaymentsEnabled);
        $this->assertTrue($institution->futureDatedPaymentsAllowed);
        $this->assertSame(['sepaCreditTransfer'], $institution->paymentsProductTypes);
        $this->assertSame(['sepaCreditTransfer'], $institution->bulkPaymentsProductTypes);
        $this->assertSame(['sepaCreditTransfer'], $institution->periodicPaymentsProductTypes);
        $this->assertSame('#7d39ff', $institution->primaryColor);
        $this->assertSame('#3DF2C2', $institution->secondaryColor);
        $this->assertNull($institution->sharedBrandName);
        $this->assertNull($institution->sharedBrandReference);
        $this->assertNull($institution->maintenanceFrom);
        $this->assertNull($institution->maintenanceTo);
        $this->assertNull($institution->maintenanceType);
    }
}
