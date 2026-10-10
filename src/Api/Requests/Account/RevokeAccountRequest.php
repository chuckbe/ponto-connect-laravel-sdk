<?php

namespace PontoConnect\Api\Requests\Account;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Revoke Account: DELETE /ponto-connect/accounts/{id}.
 */
class RevokeAccountRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected string $accountId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/accounts/'.rawurlencode($this->accountId);
    }
}
