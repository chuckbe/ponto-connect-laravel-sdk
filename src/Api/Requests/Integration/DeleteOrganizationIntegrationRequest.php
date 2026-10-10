<?php

namespace PontoConnect\Api\Requests\Integration;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Delete Organization Integration: DELETE /ponto-connect/organizations/{organizationId}/integration (client access token).
 */
class DeleteOrganizationIntegrationRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected string $organizationId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/organizations/'.rawurlencode($this->organizationId).'/integration';
    }
}
