<?php

namespace PontoConnect\Api\Resources;

use Generator;
use PontoConnect\Api\Requests\Transaction\GetTransactionRequest;
use PontoConnect\Api\Requests\Transaction\GetTransactionsRequest;
use PontoConnect\Api\Requests\Transaction\GetUpdatedTransactionsRequest;
use PontoConnect\Data\Page;
use PontoConnect\Data\Transaction;

/**
 * API reference, "Transaction". Requires a customer access token.
 */
class TransactionResource extends Resource
{
    /**
     * @return Page<Transaction>
     */
    public function list(string $accountId, ?int $limit = null, ?string $after = null, ?string $before = null): Page
    {
        return Page::fromResponse(
            $this->sendAndDecode(new GetTransactionsRequest($accountId, $limit, $after, $before)),
            fn (array $resource) => Transaction::fromArray($resource),
        );
    }

    /**
     * @return Generator<int, Transaction>
     */
    public function all(string $accountId, int $pageSize = self::MAXIMUM_PAGE_SIZE): Generator
    {
        return $this->iteratePages(fn (?string $after) => $this->list($accountId, $pageSize, $after));
    }

    public function get(string $accountId, string $transactionId): Transaction
    {
        return Transaction::fromArray(
            $this->sendAndDecodeData(new GetTransactionRequest($accountId, $transactionId)),
        );
    }

    /**
     * Transactions created or updated by the given synchronization.
     *
     * @return Page<Transaction>
     */
    public function listUpdatedForSynchronization(
        string $synchronizationId,
        ?int $limit = null,
        ?string $after = null,
        ?string $before = null,
    ): Page {
        return Page::fromResponse(
            $this->sendAndDecode(new GetUpdatedTransactionsRequest($synchronizationId, $limit, $after, $before)),
            fn (array $resource) => Transaction::fromArray($resource),
        );
    }

    /**
     * @return Generator<int, Transaction>
     */
    public function allUpdatedForSynchronization(string $synchronizationId, int $pageSize = self::MAXIMUM_PAGE_SIZE): Generator
    {
        return $this->iteratePages(
            fn (?string $after) => $this->listUpdatedForSynchronization($synchronizationId, $pageSize, $after),
        );
    }
}
