<?php

namespace PontoConnect\Api\Requests\Sandbox;

use PontoConnect\Api\Requests\Concerns\HasCursorPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List Financial Institution Transactions (sandbox): GET /ponto-connect/sandbox/financial-institutions/{financialInstitutionId}/financial-institution-accounts/{financialInstitutionAccountId}/financial-institution-transactions.
 */
class GetFinancialInstitutionTransactionsRequest extends Request
{
    use HasCursorPagination;

    protected Method $method = Method::GET;

    public function __construct(
        protected string $financialInstitutionId,
        protected string $financialInstitutionAccountId,
        protected ?int $limit = null,
        protected ?string $after = null,
        protected ?string $before = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/sandbox/financial-institutions/'.rawurlencode($this->financialInstitutionId).'/financial-institution-accounts/'.rawurlencode($this->financialInstitutionAccountId).'/financial-institution-transactions';
    }
}
