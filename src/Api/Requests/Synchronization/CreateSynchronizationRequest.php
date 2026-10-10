<?php

namespace PontoConnect\Api\Requests\Synchronization;

use PontoConnect\Api\Requests\JsonApiRequest;
use Saloon\Enums\Method;

/**
 * Create Synchronization: POST /ponto-connect/synchronizations.
 */
class CreateSynchronizationRequest extends JsonApiRequest
{
    protected Method $method = Method::POST;

    public function __construct(
        protected string $resourceType,
        protected string $resourceId,
        protected string $subtype,
        protected string $customerIpAddress,
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = $idempotencyKey;
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/synchronizations';
    }

    protected function resourceType(): string
    {
        return 'synchronization';
    }

    /**
     * @return array{resourceType: string, resourceId: string, subtype: string, customerIpAddress: string}
     */
    protected function attributes(): array
    {
        return [
            'resourceType' => $this->resourceType,
            'resourceId' => $this->resourceId,
            'subtype' => $this->subtype,
            'customerIpAddress' => $this->customerIpAddress,
        ];
    }
}
