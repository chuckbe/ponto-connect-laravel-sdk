<?php

namespace PontoConnect\Api\Requests\Account;

use PontoConnect\Api\Requests\Concerns\HasCursorPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List Accounts: GET /ponto-connect/accounts.
 */
class GetAccountsRequest extends Request
{
    use HasCursorPagination;

    protected Method $method = Method::GET;

    public function __construct(
        protected ?int $limit = null,
        protected ?string $after = null,
        protected ?string $before = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/accounts';
    }
}
