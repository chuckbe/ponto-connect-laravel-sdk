<?php

namespace PontoConnect\Api\Requests\Transaction;

use PontoConnect\Api\Requests\Concerns\HasCursorPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List Transactions: GET /ponto-connect/accounts/{accountId}/transactions.
 */
class GetTransactionsRequest extends Request
{
    use HasCursorPagination;

    protected Method $method = Method::GET;

    public function __construct(
        protected string $accountId,
        protected ?int $limit = null,
        protected ?string $after = null,
        protected ?string $before = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/accounts/'.rawurlencode($this->accountId).'/transactions';
    }
}
