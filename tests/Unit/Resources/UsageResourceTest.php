<?php

namespace PontoConnect\Tests\Unit\Resources;

use DateTimeImmutable;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class UsageResourceTest extends TestCase
{
    public function test_get_returns_the_usage_for_a_month(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('usage')]);
        $client = $this->mockedClient($mockClient, 'client-access-token');

        $usage = $client->usage->get('3953d4ab-7ec4-4c0b-8b91-4be8626c0946', '2020-07');

        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/organizations/3953d4ab-7ec4-4c0b-8b91-4be8626c0946/usage/2020-07',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertSame('2020-07', $usage->month);
        $this->assertSame('3953d4ab-7ec4-4c0b-8b91-4be8626c0946', $usage->organizationId);
        $this->assertSame('0', $usage->accountCount);
        $this->assertSame(0, $usage->totalAccounts);
        $this->assertSame(0, $usage->paymentCount);
        $this->assertSame(0, $usage->bulkPaymentCount);
        $this->assertSame(0, $usage->bulkPaymentBundleCount);
        $this->assertSame(0, $usage->paymentAccountCount);
    }

    public function test_get_accepts_a_date_and_keeps_a_prorated_account_count_exact(): void
    {
        $usageResponse = $this->fixture('usage');
        $usageResponse['data']['attributes']['accountCount'] = 1.48;
        $mockClient = new MockClient([MockResponse::make($usageResponse)]);
        $client = $this->mockedClient($mockClient, 'client-access-token');

        $usage = $client->usage->get('3953d4ab-7ec4-4c0b-8b91-4be8626c0946', new DateTimeImmutable('2020-07-15'));

        $this->assertStringEndsWith('/usage/2020-07', (string) $this->lastRequest($mockClient)->getUrl());
        $this->assertSame('1.48', $usage->accountCount);
    }
}
