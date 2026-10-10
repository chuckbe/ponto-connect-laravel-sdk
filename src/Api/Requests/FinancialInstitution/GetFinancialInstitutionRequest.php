<?php

namespace PontoConnect\Api\Requests\FinancialInstitution;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get Financial Institution: GET /ponto-connect/financial-institutions/{id}.
 */
class GetFinancialInstitutionRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $financialInstitutionId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/financial-institutions/'.rawurlencode($this->financialInstitutionId);
    }
}
