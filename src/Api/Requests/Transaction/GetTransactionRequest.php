<?php

namespace PontoConnect\Api\Requests\Transaction;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get Transaction: GET /ponto-connect/accounts/{accountId}/transactions/{id}.
 */
class GetTransactionRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $accountId,
        protected string $transactionId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/accounts/'.rawurlencode($this->accountId).'/transactions/'.rawurlencode($this->transactionId);
    }
}
