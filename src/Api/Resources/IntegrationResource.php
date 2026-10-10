<?php

namespace PontoConnect\Api\Resources;

use PontoConnect\Api\Requests\Integration\DeleteOrganizationIntegrationRequest;
use PontoConnect\Data\Integration;

/**
 * API reference, "Integration". Requires a client access token.
 */
class IntegrationResource extends Resource
{
    /**
     * Revoke the integration of an organization. Works even when the refresh
     * token is lost or expired, and stops the billing of the integration.
     */
    public function delete(string $organizationId): Integration
    {
        return Integration::fromArray(
            $this->sendAndDecodeData(new DeleteOrganizationIntegrationRequest($organizationId)),
        );
    }
}
