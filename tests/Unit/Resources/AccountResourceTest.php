<?php

namespace PontoConnect\Tests\Unit\Resources;

use DateTimeImmutable;
use PontoConnect\Data\Account;
use PontoConnect\Data\Synchronization;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class AccountResourceTest extends TestCase
{
    public function test_list_returns_a_page_of_accounts(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('accounts')]);
        $client = $this->mockedClient($mockClient);

        $page = $client->account->list(limit: 1);

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('https://api.ibanity.com/ponto-connect/accounts', $pendingRequest->getUrl());
        $this->assertSame(['page[limit]' => 1], $pendingRequest->query()->all());
        $this->assertSame('Bearer customer-access-token', $pendingRequest->headers()->get('Authorization'));
        $this->assertSame('application/vnd.api+json', $pendingRequest->headers()->get('Accept'));

        $this->assertCount(1, $page);
        $this->assertSame('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', $page->nextCursor);
        $this->assertNull($page->previousCursor);
        $this->assertSame(1, $page->limit);
        $this->assertInstanceOf(Account::class, $page->items[0]);
    }

    public function test_get_maps_every_documented_attribute(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('account')]);
        $client = $this->mockedClient($mockClient);

        $account = $client->account->get('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b');

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/accounts/5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertSame('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', $account->id);
        $this->assertSame('FR634651777754ERBI0HJTW2U88', $account->reference);
        $this->assertSame('IBAN', $account->referenceType);
        $this->assertSame('EUR', $account->currency);
        $this->assertSame('Spencer Nikolaus', $account->holderName);
        $this->assertSame('Natus magnam aliquid.', $account->description);
        $this->assertSame('Current account', $account->product);
        $this->assertSame('checking', $account->subtype);
        $this->assertSame('8e49fac4-7eb1-41d2-8c8c-30adc94c56fb', $account->internalReference);
        $this->assertFalse($account->deprecated);
        $this->assertSame('-465.33', $account->currentBalance);
        $this->assertSame('-465.33', $account->availableBalance);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:00.373Z'), $account->currentBalanceChangedAt);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T14:30:32.000Z'), $account->currentBalanceReferenceDate);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:00.380Z'), $account->currentBalanceVariationObservedAt);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:00.373Z'), $account->availableBalanceChangedAt);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T14:30:32.000Z'), $account->availableBalanceReferenceDate);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:00.380Z'), $account->availableBalanceVariationObservedAt);
        $this->assertEquals(new DateTimeImmutable('2025-04-18T14:14:26.569Z'), $account->authorizedAt);
        $this->assertEquals(new DateTimeImmutable('2025-07-17T14:14:26.569Z'), $account->authorizationExpirationExpectedAt);
        $this->assertSame('UTC', $account->authorizedAt?->getTimezone()->getName());
        $this->assertSame('available', $account->availability);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:32:00.481Z'), $account->synchronizedAt);
        $this->assertSame('41ed5259-9e07-43a7-b8c2-3854adef2067', $account->financialInstitutionId);

        $this->assertInstanceOf(Synchronization::class, $account->latestSynchronization);
        $this->assertSame('68e0e4d4-499b-437a-a058-9bed2c6987b0', $account->latestSynchronization->id);
        $this->assertSame('accountDetails', $account->latestSynchronization->subtype);
        $this->assertTrue($account->latestSynchronization->isSuccessful());
        $this->assertTrue($account->latestSynchronization->customerOnline);

        $this->assertSame('-465.33', $account->toArray()['currentBalance']);
    }

    public function test_all_walks_every_page_lazily(): void
    {
        $firstPage = $this->fixture('accounts');
        $secondPage = $this->fixture('accounts');
        $secondPage['data'][0]['id'] = 'd500ddac-27e8-447b-b0a1-dbca8c181b99';
        unset($secondPage['links']['next']);

        $mockClient = new MockClient([
            MockResponse::make($firstPage),
            MockResponse::make($secondPage),
        ]);
        $client = $this->mockedClient($mockClient);

        $accountIds = [];
        foreach ($client->account->all() as $account) {
            $accountIds[] = $account->id;
        }

        $this->assertSame(['5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', 'd500ddac-27e8-447b-b0a1-dbca8c181b99'], $accountIds);
        $this->assertSame(
            ['page[limit]' => 100, 'page[after]' => '5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b'],
            $this->lastRequest($mockClient)->query()->all(),
        );
    }

    public function test_revoke_sends_a_delete_request(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('account-revoked')]);
        $client = $this->mockedClient($mockClient);

        $client->account->revoke('5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b');

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('DELETE', $pendingRequest->getMethod()->value);
        $this->assertSame('https://api.ibanity.com/ponto-connect/accounts/5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b', $pendingRequest->getUrl());
    }
}
