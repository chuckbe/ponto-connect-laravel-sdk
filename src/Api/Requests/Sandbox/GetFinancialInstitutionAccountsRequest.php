<?php

namespace PontoConnect\Api\Requests\Sandbox;

use PontoConnect\Api\Requests\Concerns\HasCursorPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List Financial Institution Accounts (sandbox): GET /ponto-connect/sandbox/financial-institutions/{financialInstitutionId}/financial-institution-accounts.
 */
class GetFinancialInstitutionAccountsRequest extends Request
{
    use HasCursorPagination;

    protected Method $method = Method::GET;

    public function __construct(
        protected string $financialInstitutionId,
        protected ?int $limit = null,
        protected ?string $after = null,
        protected ?string $before = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/sandbox/financial-institutions/'.rawurlencode($this->financialInstitutionId).'/financial-institution-accounts';
    }
}
