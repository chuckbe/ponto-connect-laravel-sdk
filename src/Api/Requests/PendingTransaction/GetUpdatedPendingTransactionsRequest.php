<?php

namespace PontoConnect\Api\Requests\PendingTransaction;

use PontoConnect\Api\Requests\Concerns\HasCursorPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List Updated Pending Transactions for Synchronization: GET /ponto-connect/synchronizations/{synchronizationId}/updated-pending-transactions.
 */
class GetUpdatedPendingTransactionsRequest extends Request
{
    use HasCursorPagination;

    protected Method $method = Method::GET;

    public function __construct(
        protected string $synchronizationId,
        protected ?int $limit = null,
        protected ?string $after = null,
        protected ?string $before = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/synchronizations/'.rawurlencode($this->synchronizationId).'/updated-pending-transactions';
    }
}
