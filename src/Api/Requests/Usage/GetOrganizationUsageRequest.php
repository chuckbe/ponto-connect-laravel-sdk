<?php

namespace PontoConnect\Api\Requests\Usage;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get Organization Usage: GET /ponto-connect/organizations/{organizationId}/usage/{month} (client access token).
 */
class GetOrganizationUsageRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $organizationId,
        protected string $month,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/organizations/'.rawurlencode($this->organizationId).'/usage/'.rawurlencode($this->month);
    }
}
