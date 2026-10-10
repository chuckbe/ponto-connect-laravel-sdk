<?php

namespace PontoConnect\Api\Requests\OAuth;

use SensitiveParameter;

/**
 * Request Initial Tokens: POST /ponto-connect/oauth2/token (grant_type=authorization_code).
 */
class ExchangeAuthorizationCodeRequest extends OAuthRequest
{
    public function __construct(
        string $clientId,
        #[SensitiveParameter]
        string $clientSecret,
        #[SensitiveParameter]
        protected string $authorizationCode,
        protected string $redirectUri,
        #[SensitiveParameter]
        protected string $codeVerifier,
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
            'grant_type' => 'authorization_code',
            'code' => $this->authorizationCode,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => $this->codeVerifier,
        ];
    }
}
