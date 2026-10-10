<?php

namespace PontoConnect\Tests\Unit\Resources;

use DateTimeImmutable;
use PontoConnect\Enums\SynchronizationSubtype;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class SynchronizationResourceTest extends TestCase
{
    public function test_create_sends_the_customer_ip_address_and_idempotency_key(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('synchronization-created', 201)]);
        $client = $this->mockedClient($mockClient);

        $synchronization = $client->synchronization->create(
            resourceId: '5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b',
            subtype: SynchronizationSubtype::ACCOUNT_DETAILS,
            customerIpAddress: '123.123.123.123',
            idempotencyKey: 'fdba3f56-f4ce-4b90-8a89-fc64ea7ba2dd',
        );

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('POST', $pendingRequest->getMethod()->value);
        $this->assertSame('https://api.ibanity.com/ponto-connect/synchronizations', $pendingRequest->getUrl());
        $this->assertSame('application/vnd.api+json', $pendingRequest->headers()->get('Content-Type'));
        $this->assertSame('fdba3f56-f4ce-4b90-8a89-fc64ea7ba2dd', $pendingRequest->headers()->get('Ibanity-Idempotency-Key'));
        $this->assertSame([
            'data' => [
                'type' => 'synchronization',
                'attributes' => [
                    'resourceType' => 'account',
                    'resourceId' => '5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b',
                    'subtype' => 'accountDetails',
                    'customerIpAddress' => '123.123.123.123',
                ],
            ],
        ], $pendingRequest->body()?->all());

        $this->assertSame('68e0e4d4-499b-437a-a058-9bed2c6987b0', $synchronization->id);
        $this->assertTrue($synchronization->isPending());
        $this->assertFalse($synchronization->isFinished());
    }

    public function test_create_without_idempotency_key_omits_the_header(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('synchronization-created', 201)]);
        $client = $this->mockedClient($mockClient);

        $client->synchronization->create('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', 'accountTransactions', '123.123.123.123');

        $this->assertNull($this->lastRequest($mockClient)->headers()->get('Ibanity-Idempotency-Key'));
    }

    public function test_get_returns_the_synchronization(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('synchronization')]);
        $client = $this->mockedClient($mockClient);

        $synchronization = $client->synchronization->get('89cfd963-683a-4deb-bdc4-131655b53414');

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/synchronizations/89cfd963-683a-4deb-bdc4-131655b53414',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertSame('success', $synchronization->status);
        $this->assertSame('accountTransactionsWithUnsettled', $synchronization->subtype);
        $this->assertSame('account', $synchronization->resourceType);
        $this->assertSame('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', $synchronization->resourceId);
        $this->assertSame([], $synchronization->errors);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:42.907Z'), $synchronization->createdAt);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:43.340Z'), $synchronization->updatedAt);
        $this->assertTrue($synchronization->isFinished());
    }

    public function test_failed_synchronization_exposes_error_codes(): void
    {
        $failed = $this->fixture('synchronization');
        $failed['data']['attributes']['status'] = 'error';
        $failed['data']['attributes']['errors'] = [
            ['code' => 'authorizationExpired', 'detail' => 'The authorization has expired.'],
        ];
        $mockClient = new MockClient([MockResponse::make($failed)]);
        $client = $this->mockedClient($mockClient);

        $synchronization = $client->synchronization->get('89cfd963-683a-4deb-bdc4-131655b53414');

        $this->assertTrue($synchronization->hasFailed());
        $this->assertSame(['authorizationExpired'], $synchronization->errorCodes());
    }
}
