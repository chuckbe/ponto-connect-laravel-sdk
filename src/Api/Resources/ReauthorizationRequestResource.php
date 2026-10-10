<?php

namespace PontoConnect\Api\Resources;

use PontoConnect\Api\Requests\ReauthorizationRequest\RequestAccountReauthorizationRequest;
use PontoConnect\Data\ReauthorizationRequest;

/**
 * API reference, "Reauthorization Request". Requires a customer access token.
 */
class ReauthorizationRequestResource extends Resource
{
    /**
     * Redirect the customer to the returned `redirectUrl` to reauthorize the
     * account at their bank; Ponto sends them back to `$redirectUri` afterwards.
     */
    public function create(string $accountId, string $redirectUri, ?string $idempotencyKey = null): ReauthorizationRequest
    {
        return ReauthorizationRequest::fromArray($this->sendAndDecodeData(
            new RequestAccountReauthorizationRequest($accountId, $redirectUri, $idempotencyKey),
        ));
    }
}
