<?php

namespace PontoConnect\Api\Requests\IntegrationAccount;

use PontoConnect\Api\Requests\Concerns\HasCursorPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List Integration Account: GET /ponto-connect/integration-accounts (client access token).
 */
class GetIntegrationAccountsRequest extends Request
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
        return '/ponto-connect/integration-accounts';
    }
}
