<?php

namespace PontoConnect\Api\Resources;

use DateTimeInterface;
use PontoConnect\Api\Requests\Usage\GetOrganizationUsageRequest;
use PontoConnect\Data\Usage;

/**
 * API reference, "Usage". Requires a client access token.
 */
class UsageResource extends Resource
{
    /**
     * @param string|DateTimeInterface $month a "YYYY-MM" string or any date in that month
     */
    public function get(string $organizationId, string|DateTimeInterface $month): Usage
    {
        if ($month instanceof DateTimeInterface) {
            $month = $month->format('Y-m');
        }

        return Usage::fromArray($this->sendAndDecodeData(new GetOrganizationUsageRequest($organizationId, $month)));
    }
}
