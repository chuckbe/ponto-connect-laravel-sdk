<?php

namespace PontoConnect\Api\Requests\OAuth;

use SensitiveParameter;

/**
 * Request Access Token: POST /ponto-connect/oauth2/token (grant_type=refresh_token).
 */
class RefreshAccessTokenRequest extends OAuthRequest
{
    public function __construct(
        string $clientId,
        #[SensitiveParameter]
        string $clientSecret,
        #[SensitiveParameter]
        protected string $refreshToken,
    ) {
        parent::__construct($clientId, $clientSecret);
    }

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
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->clientId,
        ];
    }
}
