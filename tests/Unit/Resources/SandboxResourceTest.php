<?php

namespace PontoConnect\Tests\Unit\Resources;

use DateTimeImmutable;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

class SandboxResourceTest extends TestCase
{
    private const FINANCIAL_INSTITUTION_ID = '41ed5259-9e07-43a7-b8c2-3854adef2067';

    private const FINANCIAL_INSTITUTION_ACCOUNT_ID = '8e49fac4-7eb1-41d2-8c8c-30adc94c56fb';

    private const BASE_URL = 'https://api.ibanity.com/ponto-connect/sandbox/financial-institutions/41ed5259-9e07-43a7-b8c2-3854adef2067/financial-institution-accounts';

    public function test_list_financial_institution_accounts(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('sandbox-financial-institution-accounts')]);
        $client = $this->mockedClient($mockClient);

        $page = $client->sandbox->listFinancialInstitutionAccounts(self::FINANCIAL_INSTITUTION_ID, limit: 1);

        $this->assertSame(self::BASE_URL, $this->lastRequest($mockClient)->getUrl());
        $this->assertSame(self::FINANCIAL_INSTITUTION_ACCOUNT_ID, $page->items[0]->id);
        $this->assertSame('-464.75', $page->items[0]->currentBalance);
        $this->assertSame(self::FINANCIAL_INSTITUTION_ACCOUNT_ID, $page->nextCursor);
    }

    public function test_get_financial_institution_account(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('sandbox-financial-institution-account')]);
        $client = $this->mockedClient($mockClient);

        $account = $client->sandbox->getFinancialInstitutionAccount(self::FINANCIAL_INSTITUTION_ID, self::FINANCIAL_INSTITUTION_ACCOUNT_ID);

        $this->assertSame(self::BASE_URL.'/'.self::FINANCIAL_INSTITUTION_ACCOUNT_ID, $this->lastRequest($mockClient)->getUrl());
        $this->assertSame('FR634651777754ERBI0HJTW2U88', $account->reference);
        $this->assertSame('IBAN', $account->referenceType);
        $this->assertSame('-464.75', $account->availableBalance);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T13:31:57.530551Z'), $account->availableBalanceChangedAt);
        $this->assertEquals(new DateTimeImmutable('2025-04-22T14:30:32Z'), $account->currentBalanceReferenceDate);
    }

    public function test_list_and_get_financial_institution_transactions(): void
    {
        $mockClient = new MockClient([
            $this->fixtureResponse('sandbox-financial-institution-transactions'),
            $this->fixtureResponse('sandbox-financial-institution-transaction'),
        ]);
        $client = $this->mockedClient($mockClient);

        $page = $client->sandbox->listFinancialInstitutionTransactions(self::FINANCIAL_INSTITUTION_ID, self::FINANCIAL_INSTITUTION_ACCOUNT_ID);
        $this->assertSame(
            self::BASE_URL.'/'.self::FINANCIAL_INSTITUTION_ACCOUNT_ID.'/financial-institution-transactions',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertNotEmpty($page->items);

        $transaction = $client->sandbox->getFinancialInstitutionTransaction(
            self::FINANCIAL_INSTITUTION_ID,
            self::FINANCIAL_INSTITUTION_ACCOUNT_ID,
            '2468a217-d68d-4a57-8a94-5803ef5eb35e',
        );
        $this->assertSame(
            self::BASE_URL.'/'.self::FINANCIAL_INSTITUTION_ACCOUNT_ID.'/financial-institution-transactions/2468a217-d68d-4a57-8a94-5803ef5eb35e',
            $this->lastRequest($mockClient)->getUrl(),
        );
        $this->assertNotSame('', $transaction->id);
    }

    public function test_create_financial_institution_transaction_with_a_structured_reference(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('sandbox-financial-institution-transaction-created', 201)]);
        $client = $this->mockedClient($mockClient);

        $transaction = $client->sandbox->createFinancialInstitutionTransaction(
            self::FINANCIAL_INSTITUTION_ID,
            self::FINANCIAL_INSTITUTION_ACCOUNT_ID,
            [
                'valueDate' => new DateTimeImmutable('2020-05-22T02:00:00+02:00'),
                'executionDate' => '2020-05-25T00:00:00Z',
                'amount' => '84.42',
                'currency' => 'EUR',
                'description' => 'Invoice 2020-0042',
                'remittanceInformationType' => 'structured',
                'remittanceInformation' => '+++090/9337/55493+++',
                'counterpartName' => 'Otro Bank',
                'counterpartReference' => 'BE9786154282554',
                'fee' => null,
            ],
            'e3b0b6a2-9a43-4c1a-8a35-6c2a7c5a0b11',
        );

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('POST', $pendingRequest->getMethod()->value);
        $this->assertSame(
            self::BASE_URL.'/'.self::FINANCIAL_INSTITUTION_ACCOUNT_ID.'/financial-institution-transactions',
            $pendingRequest->getUrl(),
        );
        $this->assertSame('e3b0b6a2-9a43-4c1a-8a35-6c2a7c5a0b11', $pendingRequest->headers()->get('Ibanity-Idempotency-Key'));
        $this->assertSame([
            'data' => [
                'type' => 'financialInstitutionTransaction',
                'attributes' => [
                    'valueDate' => '2020-05-22T00:00:00Z',
                    'executionDate' => '2020-05-25T00:00:00Z',
                    'amount' => 84.42,
                    'currency' => 'EUR',
                    'description' => 'Invoice 2020-0042',
                    'remittanceInformationType' => 'structured',
                    'remittanceInformation' => '+++090/9337/55493+++',
                    'counterpartName' => 'Otro Bank',
                    'counterpartReference' => 'BE9786154282554',
                ],
            ],
        ], $pendingRequest->body()?->all());
        $this->assertStringContainsString('"amount":84.42', (string) $pendingRequest->createPsrRequest()->getBody());

        $this->assertSame('fa74b3e2-6bd7-43a5-a92d-0df015c5e4a0', $transaction->id);
        $this->assertSame('84.42', $transaction->amount);
        $this->assertSame('3.14', $transaction->fee);
        $this->assertSame('MASKEDPAN', $transaction->cardReferenceType);
        $this->assertEquals(new DateTimeImmutable('2020-05-22T00:00:00Z'), $transaction->valueDate);
    }

    public function test_update_financial_institution_transaction(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('sandbox-financial-institution-transaction-updated')]);
        $client = $this->mockedClient($mockClient);

        $transaction = $client->sandbox->updateFinancialInstitutionTransaction(
            self::FINANCIAL_INSTITUTION_ID,
            self::FINANCIAL_INSTITUTION_ACCOUNT_ID,
            'ac966e44-05ea-4259-82f7-01949e6341c7',
            ['counterpartName' => 'Otro Bank', 'remittanceInformation' => 'NEW SHOES'],
        );

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('PATCH', $pendingRequest->getMethod()->value);
        $this->assertSame(
            self::BASE_URL.'/'.self::FINANCIAL_INSTITUTION_ACCOUNT_ID.'/financial-institution-transactions/ac966e44-05ea-4259-82f7-01949e6341c7',
            $pendingRequest->getUrl(),
        );
        $this->assertSame(
            ['counterpartName' => 'Otro Bank', 'remittanceInformation' => 'NEW SHOES'],
            $pendingRequest->body()?->all()['data']['attributes'],
        );
        $this->assertSame('-85.00', $transaction->amount);
    }
}
