<?php

namespace PontoConnect\Api\Resources;

use PontoConnect\Api\Requests\Synchronization\CreateSynchronizationRequest;
use PontoConnect\Api\Requests\Synchronization\GetSynchronizationRequest;
use PontoConnect\Data\Synchronization;
use PontoConnect\Enums\SynchronizationSubtype;

/**
 * API reference, "Synchronization". Requires a customer access token.
 */
class SynchronizationResource extends Resource
{
    /**
     * Ask Ponto to synchronize an account with the bank.
     *
     * Only allowed while the customer is actively using your application:
     * pass their real IP address. Ponto refuses a new synchronization of the
     * same account and subtype within 30 minutes of the previous one.
     */
    public function create(
        string $resourceId,
        SynchronizationSubtype|string $subtype,
        string $customerIpAddress,
        string $resourceType = 'account',
        ?string $idempotencyKey = null,
    ): Synchronization {
        if ($subtype instanceof SynchronizationSubtype) {
            $subtype = $subtype->value;
        }

        return Synchronization::fromArray($this->sendAndDecodeData(new CreateSynchronizationRequest(
            resourceType: $resourceType,
            resourceId: $resourceId,
            subtype: $subtype,
            customerIpAddress: $customerIpAddress,
            idempotencyKey: $idempotencyKey,
        )));
    }

    public function get(string $synchronizationId): Synchronization
    {
        return Synchronization::fromArray($this->sendAndDecodeData(new GetSynchronizationRequest($synchronizationId)));
    }
}
