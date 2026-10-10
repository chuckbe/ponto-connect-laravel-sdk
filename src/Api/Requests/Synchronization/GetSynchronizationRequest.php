<?php

namespace PontoConnect\Api\Requests\Synchronization;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get Synchronization: GET /ponto-connect/synchronizations/{id}.
 */
class GetSynchronizationRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $synchronizationId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/synchronizations/'.rawurlencode($this->synchronizationId);
    }
}
