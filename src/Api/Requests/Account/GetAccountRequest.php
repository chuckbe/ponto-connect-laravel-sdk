<?php

namespace PontoConnect\Api\Requests\Account;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get Account: GET /ponto-connect/accounts/{id}.
 */
class GetAccountRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $accountId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/accounts/'.rawurlencode($this->accountId);
    }
}
