<?php

namespace PontoConnect\Api\Resources;

use Generator;
use PontoConnect\Api\Requests\PendingTransaction\GetPendingTransactionRequest;
use PontoConnect\Api\Requests\PendingTransaction\GetPendingTransactionsRequest;
use PontoConnect\Api\Requests\PendingTransaction\GetUpdatedPendingTransactionsRequest;
use PontoConnect\Data\Page;
use PontoConnect\Data\PendingTransaction;

/**
 * API reference, "Pending Transaction". Requires a customer access token.
 * Only available when the financial institution has `pendingTransactionsAvailable`.
 */
class PendingTransactionResource extends Resource
{
    /**
     * @return Page<PendingTransaction>
     */
    public function list(string $accountId, ?int $limit = null, ?string $after = null, ?string $before = null): Page
    {
        return Page::fromResponse(
            $this->sendAndDecode(new GetPendingTransactionsRequest($accountId, $limit, $after, $before)),
            fn (array $resource) => PendingTransaction::fromArray($resource),
        );
    }

    /**
     * @return Generator<int, PendingTransaction>
     */
    public function all(string $accountId, int $pageSize = self::MAXIMUM_PAGE_SIZE): Generator
    {
        return $this->iteratePages(fn (?string $after) => $this->list($accountId, $pageSize, $after));
    }

    public function get(string $accountId, string $pendingTransactionId): PendingTransaction
    {
        return PendingTransaction::fromArray(
            $this->sendAndDecodeData(new GetPendingTransactionRequest($accountId, $pendingTransactionId)),
        );
    }

    /**
     * @return Page<PendingTransaction>
     */
    public function listUpdatedForSynchronization(
        string $synchronizationId,
        ?int $limit = null,
        ?string $after = null,
        ?string $before = null,
    ): Page {
        return Page::fromResponse(
            $this->sendAndDecode(new GetUpdatedPendingTransactionsRequest($synchronizationId, $limit, $after, $before)),
            fn (array $resource) => PendingTransaction::fromArray($resource),
        );
    }

    /**
     * @return Generator<int, PendingTransaction>
     */
    public function allUpdatedForSynchronization(string $synchronizationId, int $pageSize = self::MAXIMUM_PAGE_SIZE): Generator
    {
        return $this->iteratePages(
            fn (?string $after) => $this->listUpdatedForSynchronization($synchronizationId, $pageSize, $after),
        );
    }
}
