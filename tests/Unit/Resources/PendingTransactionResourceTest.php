<?php

namespace PontoConnect\Tests\Unit\Resources;

use PontoConnect\Data\PendingTransaction;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

class PendingTransactionResourceTest extends TestCase
{
    public function test_list_returns_pending_transactions(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('pending-transactions')]);
        $client = $this->mockedClient($mockClient);

        $page = $client->pendingTransaction->list('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b');

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/accounts/5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b/pending-transactions',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertInstanceOf(PendingTransaction::class, $page->items[0]);
        $this->assertSame('-21.24', $page->items[0]->amount);
        $this->assertSame('ea0ba58d-5542-42b3-9a4e-08f00405a2ff', $page->nextCursor);
    }

    public function test_get_returns_a_pending_transaction(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('pending-transaction')]);
        $client = $this->mockedClient($mockClient);

        $pendingTransaction = $client->pendingTransaction->get('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', 'bac0f85d-f1d8-4e15-b46d-fa305ff46848');

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/accounts/5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b/pending-transactions/bac0f85d-f1d8-4e15-b46d-fa305ff46848',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertInstanceOf(PendingTransaction::class, $pendingTransaction);
        $this->assertSame('-99.29', $pendingTransaction->amount);
        $this->assertSame('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', $pendingTransaction->accountId);
    }

    public function test_list_updated_for_synchronization(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('updated-pending-transactions')]);
        $client = $this->mockedClient($mockClient);

        $page = $client->pendingTransaction->listUpdatedForSynchronization('43a16d81-53a1-4f26-93e9-3b0e8a4007d0');

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/synchronizations/43a16d81-53a1-4f26-93e9-3b0e8a4007d0/updated-pending-transactions',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertInstanceOf(PendingTransaction::class, $page->items[0]);
    }
}
