<?php

namespace PontoConnect\Api\Requests\Sandbox;

use PontoConnect\Api\Requests\Concerns\NormalizesSandboxTransactionAttributes;
use PontoConnect\Api\Requests\JsonApiRequest;
use Saloon\Enums\Method;

/**
 * Update Financial Institution Transaction (sandbox):
 * PATCH /ponto-connect/sandbox/financial-institutions/{financialInstitutionId}/financial-institution-accounts/{financialInstitutionAccountId}/financial-institution-transactions/{id}.
 */
class UpdateFinancialInstitutionTransactionRequest extends JsonApiRequest
{
    use NormalizesSandboxTransactionAttributes;

    protected Method $method = Method::PATCH;

    /**
     * @param array<string, mixed> $transactionAttributes
     */
    public function __construct(
        protected string $financialInstitutionId,
        protected string $financialInstitutionAccountId,
        protected string $financialInstitutionTransactionId,
        protected array $transactionAttributes,
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = $idempotencyKey;
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/sandbox/financial-institutions/'.rawurlencode($this->financialInstitutionId)
            .'/financial-institution-accounts/'.rawurlencode($this->financialInstitutionAccountId)
            .'/financial-institution-transactions/'.rawurlencode($this->financialInstitutionTransactionId);
    }

    protected function resourceType(): string
    {
        return 'financialInstitutionTransaction';
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(): array
    {
        return $this->normalizeTransactionAttributes($this->transactionAttributes);
    }
}
