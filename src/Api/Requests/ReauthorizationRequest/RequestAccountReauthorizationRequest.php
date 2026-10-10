<?php

namespace PontoConnect\Api\Requests\ReauthorizationRequest;

use PontoConnect\Api\Requests\JsonApiRequest;
use Saloon\Enums\Method;

/**
 * Request Account Reauthorization: POST /ponto-connect/accounts/{accountId}/reauthorization-requests.
 */
class RequestAccountReauthorizationRequest extends JsonApiRequest
{
    protected Method $method = Method::POST;

    public function __construct(
        protected string $accountId,
        protected string $redirectUri,
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = $idempotencyKey;
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/accounts/'.rawurlencode($this->accountId).'/reauthorization-requests';
    }

    protected function resourceType(): string
    {
        return 'reauthorizationRequest';
    }

    /**
     * @return array{redirectUri: string}
     */
    protected function attributes(): array
    {
        return [
            'redirectUri' => $this->redirectUri,
        ];
    }
}
