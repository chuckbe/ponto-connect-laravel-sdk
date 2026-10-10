<?php

namespace PontoConnect\Api\Requests\Sandbox;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get Financial Institution Account (sandbox): GET /ponto-connect/sandbox/financial-institutions/{financialInstitutionId}/financial-institution-accounts/{id}.
 */
class GetFinancialInstitutionAccountRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $financialInstitutionId,
        protected string $financialInstitutionAccountId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/sandbox/financial-institutions/'.rawurlencode($this->financialInstitutionId).'/financial-institution-accounts/'.rawurlencode($this->financialInstitutionAccountId);
    }
}
