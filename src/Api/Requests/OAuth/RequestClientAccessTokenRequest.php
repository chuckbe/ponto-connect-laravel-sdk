<?php

namespace PontoConnect\Api\Requests\OAuth;

/**
 * Request Client Access Token: POST /ponto-connect/oauth2/token (grant_type=client_credentials).
 */
class RequestClientAccessTokenRequest extends OAuthRequest
{
    public function resolveEndpoint(): string
    {
        return '/ponto-connect/oauth2/token';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'grant_type' => 'client_credentials',
        ];
    }
}
