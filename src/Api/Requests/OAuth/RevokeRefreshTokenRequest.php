<?php

namespace PontoConnect\Api\Requests\OAuth;

use SensitiveParameter;

/**
 * Revoke Refresh Token: POST /ponto-connect/oauth2/revoke.
 */
class RevokeRefreshTokenRequest extends OAuthRequest
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
        return '/ponto-connect/oauth2/revoke';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'token' => $this->refreshToken,
        ];
    }
}
