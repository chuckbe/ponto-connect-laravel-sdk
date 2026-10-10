<?php

namespace PontoConnect\Api\Requests\PendingTransaction;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get Pending Transaction: GET /ponto-connect/accounts/{accountId}/pending-transactions/{id}.
 */
class GetPendingTransactionRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $accountId,
        protected string $pendingTransactionId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/accounts/'.rawurlencode($this->accountId).'/pending-transactions/'.rawurlencode($this->pendingTransactionId);
    }
}
