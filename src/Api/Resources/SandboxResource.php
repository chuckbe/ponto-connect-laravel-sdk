<?php

namespace PontoConnect\Api\Resources;

use DateTimeInterface;
use PontoConnect\Api\Requests\Sandbox\CreateFinancialInstitutionTransactionRequest;
use PontoConnect\Api\Requests\Sandbox\GetFinancialInstitutionAccountRequest;
use PontoConnect\Api\Requests\Sandbox\GetFinancialInstitutionAccountsRequest;
use PontoConnect\Api\Requests\Sandbox\GetFinancialInstitutionTransactionRequest;
use PontoConnect\Api\Requests\Sandbox\GetFinancialInstitutionTransactionsRequest;
use PontoConnect\Api\Requests\Sandbox\UpdateFinancialInstitutionTransactionRequest;
use PontoConnect\Data\FinancialInstitutionAccount;
use PontoConnect\Data\FinancialInstitutionTransaction;
use PontoConnect\Data\Page;

/**
 * API reference, "Sandbox Resources": fake accounts and transactions for
 * testing. Requires a customer access token of a sandbox integration; live
 * applications get a 403.
 */
class SandboxResource extends Resource
{
    /**
     * @return Page<FinancialInstitutionAccount>
     */
    public function listFinancialInstitutionAccounts(
        string $financialInstitutionId,
        ?int $limit = null,
        ?string $after = null,
        ?string $before = null,
    ): Page {
        return Page::fromResponse(
            $this->sendAndDecode(new GetFinancialInstitutionAccountsRequest($financialInstitutionId, $limit, $after, $before)),
            fn (array $resource) => FinancialInstitutionAccount::fromArray($resource),
        );
    }

    public function getFinancialInstitutionAccount(
        string $financialInstitutionId,
        string $financialInstitutionAccountId,
    ): FinancialInstitutionAccount {
        return FinancialInstitutionAccount::fromArray($this->sendAndDecodeData(
            new GetFinancialInstitutionAccountRequest($financialInstitutionId, $financialInstitutionAccountId),
        ));
    }

    /**
     * @return Page<FinancialInstitutionTransaction>
     */
    public function listFinancialInstitutionTransactions(
        string $financialInstitutionId,
        string $financialInstitutionAccountId,
        ?int $limit = null,
        ?string $after = null,
        ?string $before = null,
    ): Page {
        return Page::fromResponse(
            $this->sendAndDecode(new GetFinancialInstitutionTransactionsRequest(
                $financialInstitutionId,
                $financialInstitutionAccountId,
                $limit,
                $after,
                $before,
            )),
            fn (array $resource) => FinancialInstitutionTransaction::fromArray($resource),
        );
    }

    public function getFinancialInstitutionTransaction(
        string $financialInstitutionId,
        string $financialInstitutionAccountId,
        string $financialInstitutionTransactionId,
    ): FinancialInstitutionTransaction {
        return FinancialInstitutionTransaction::fromArray($this->sendAndDecodeData(
            new GetFinancialInstitutionTransactionRequest(
                $financialInstitutionId,
                $financialInstitutionAccountId,
                $financialInstitutionTransactionId,
            ),
        ));
    }

    /**
     * Create a fake transaction, e.g. an incoming payment with a structured
     * reference. It appears in the account's transactions after the next
     * synchronization. Amounts may be passed as decimal strings.
     *
     * @param array{
     *     valueDate: string|DateTimeInterface,
     *     executionDate: string|DateTimeInterface,
     *     amount: string|int|float,
     *     currency: string,
     *     description: string,
     *     remittanceInformationType: string,
     *     remittanceInformation?: string|null,
     *     counterpartName?: string|null,
     *     counterpartReference?: string|null,
     *     endToEndId?: string|null,
     *     purposeCode?: string|null,
     *     mandateId?: string|null,
     *     creditorId?: string|null,
     *     additionalInformation?: string|null,
     *     proprietaryBankTransactionCode?: string|null,
     *     bankTransactionCode?: string|null,
     *     cardReferenceType?: string|null,
     *     cardReference?: string|null,
     *     fee?: string|int|float|null,
     * } $attributes
     */
    public function createFinancialInstitutionTransaction(
        string $financialInstitutionId,
        string $financialInstitutionAccountId,
        array $attributes,
        ?string $idempotencyKey = null,
    ): FinancialInstitutionTransaction {
        return FinancialInstitutionTransaction::fromArray($this->sendAndDecodeData(
            new CreateFinancialInstitutionTransactionRequest(
                $financialInstitutionId,
                $financialInstitutionAccountId,
                $attributes,
                $idempotencyKey,
            ),
        ));
    }

    /**
     * @param array{
     *     counterpartName?: string|null,
     *     description?: string|null,
     *     remittanceInformation?: string|null,
     *     endToEndId?: string|null,
     *     purposeCode?: string|null,
     *     mandateId?: string|null,
     *     creditorId?: string|null,
     *     additionalInformation?: string|null,
     *     proprietaryBankTransactionCode?: string|null,
     *     bankTransactionCode?: string|null,
     * } $attributes
     */
    public function updateFinancialInstitutionTransaction(
        string $financialInstitutionId,
        string $financialInstitutionAccountId,
        string $financialInstitutionTransactionId,
        array $attributes,
        ?string $idempotencyKey = null,
    ): FinancialInstitutionTransaction {
        return FinancialInstitutionTransaction::fromArray($this->sendAndDecodeData(
            new UpdateFinancialInstitutionTransactionRequest(
                $financialInstitutionId,
                $financialInstitutionAccountId,
                $financialInstitutionTransactionId,
                $attributes,
                $idempotencyKey,
            ),
        ));
    }
}
