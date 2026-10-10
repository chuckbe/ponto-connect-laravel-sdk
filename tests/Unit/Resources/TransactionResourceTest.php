<?php

namespace PontoConnect\Tests\Unit\Resources;

use DateTimeImmutable;
use PontoConnect\Data\Transaction;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class TransactionResourceTest extends TestCase
{
    public function test_list_returns_transactions_with_the_latest_synchronization(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('transactions')]);
        $client = $this->mockedClient($mockClient);

        $page = $client->transaction->list('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', limit: 1, after: '80cdb338-0da8-4039-863c-b108943c1e22');

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/accounts/5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b/transactions',
            $pendingRequest->getUrl(),
        );
        $this->assertSame(
            ['page[limit]' => 1, 'page[after]' => '80cdb338-0da8-4039-863c-b108943c1e22'],
            $pendingRequest->query()->all(),
        );

        $this->assertCount(1, $page);
        $this->assertSame('0365739f-9238-40ba-839b-f39f1f11890a', $page->nextCursor);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:43.340Z'), $page->synchronizedAt);
        $this->assertSame('accountTransactionsWithUnsettled', $page->latestSynchronization?->subtype);
    }

    public function test_get_maps_every_documented_attribute(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('transaction')]);
        $client = $this->mockedClient($mockClient);

        $transaction = $client->transaction->get('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', '0365739f-9238-40ba-839b-f39f1f11890a');

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/accounts/5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b/transactions/0365739f-9238-40ba-839b-f39f1f11890a',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame('0365739f-9238-40ba-839b-f39f1f11890a', $transaction->id);
        $this->assertSame('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', $transaction->accountId);
        $this->assertSame('-85.00', $transaction->amount);
        $this->assertSame('EUR', $transaction->currency);
        $this->assertEquals(new DateTimeImmutable('2025-04-19T13:32:00.000Z'), $transaction->valueDate);
        $this->assertEquals(new DateTimeImmutable('2025-04-19T13:32:00.000Z'), $transaction->executionDate);
        $this->assertNull($transaction->counterpartName);
        $this->assertSame('BE36732915527881', $transaction->counterpartReference);
        $this->assertSame('BIGGER NEW JACKET', $transaction->remittanceInformation);
        $this->assertSame('unstructured', $transaction->remittanceInformationType);
        $this->assertSame('Big Cotton JACKET', $transaction->description);
        $this->assertSame('Online payment on bestmarket.com', $transaction->additionalInformation);
        $this->assertSame('76061f262404431bac46c344482f0fea', $transaction->endToEndId);
        $this->assertSame('234', $transaction->mandateId);
        $this->assertSame('123498765421', $transaction->creditorId);
        $this->assertSame('CASH', $transaction->purposeCode);
        $this->assertSame('PMNT-IRCT-ESCT', $transaction->bankTransactionCode);
        $this->assertSame('12345', $transaction->proprietaryBankTransactionCode);
        $this->assertNull($transaction->cardReference);
        $this->assertNull($transaction->cardReferenceType);
        $this->assertSame('d8415e8d-87a8-4b34-80e1-0c4fbf687e36', $transaction->internalReference);
        $this->assertSame('fa026f630b82f305a66d170245be6069dd8c846ee610a7b842ca3ef81d3c6165', $transaction->digest);
        $this->assertNull($transaction->fee);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:21.513Z'), $transaction->createdAt);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:43.158Z'), $transaction->updatedAt);
    }

    public function test_list_updated_for_synchronization(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('updated-transactions')]);
        $client = $this->mockedClient($mockClient);

        $page = $client->transaction->listUpdatedForSynchronization('43a16d81-53a1-4f26-93e9-3b0e8a4007d0', limit: 1);

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/synchronizations/43a16d81-53a1-4f26-93e9-3b0e8a4007d0/updated-transactions',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertSame('36ebdb8b-6166-465d-a250-028e0cb94efa', $page->items[0]->id);
        $this->assertSame('36ebdb8b-6166-465d-a250-028e0cb94efa', $page->nextCursor);
    }

    public function test_all_updated_for_synchronization_stops_without_next_link(): void
    {
        $lastPage = $this->fixture('updated-transactions');
        unset($lastPage['links']['next']);

        $mockClient = new MockClient([MockResponse::make($lastPage)]);
        $client = $this->mockedClient($mockClient);

        $transactions = iterator_to_array($client->transaction->allUpdatedForSynchronization('43a16d81-53a1-4f26-93e9-3b0e8a4007d0'), false);

        $this->assertCount(1, $transactions);
        $this->assertCount(1, $mockClient->getRecordedResponses());
    }
}
