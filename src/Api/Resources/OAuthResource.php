<?php

namespace PontoConnect\Api\Resources;

use PontoConnect\Api\Requests\OAuth\ExchangeAuthorizationCodeRequest;
use PontoConnect\Api\Requests\OAuth\RefreshAccessTokenRequest;
use PontoConnect\Api\Requests\OAuth\RequestClientAccessTokenRequest;
use PontoConnect\Api\Requests\OAuth\RevokeRefreshTokenRequest;
use PontoConnect\Data\Token;
use PontoConnect\Enums\Language;
use PontoConnect\Enums\Scope;
use PontoConnect\Exceptions\InvalidGrantException;
use SensitiveParameter;

/**
 * Authorization resources (API reference, "Token") and the authorization URL
 * from the Ponto Connect guide ("Access Authorization", step 2).
 */
class OAuthResource extends Resource
{
    /**
     * Build the URL that sends the customer to Ponto to authorize your application.
     * This method does not call the API.
     *
     * @param list<string|Scope>|null $scopes defaults to the configured scopes
     */
    public function authorizationUrl(
        string $redirectUri,
        string $state,
        string $codeChallenge,
        ?array $scopes = null,
        Language|string|null $language = null,
        ?string $onboardingDetailsId = null,
    ): string {
        $configuration = $this->connector->configuration();

        $scopeValues = [];
        foreach ($scopes ?? $configuration->scopes as $scope) {
            if ($scope instanceof Scope) {
                $scope = $scope->value;
            }

            $scopeValues[] = $scope;
        }

        $parameters = [
            'client_id' => $configuration->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopeValues),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        if ($onboardingDetailsId !== null) {
            $parameters['onboarding_details_id'] = $onboardingDetailsId;
        }

        if ($language instanceof Language) {
            $language = $language->value;
        }

        if ($language !== null) {
            $parameters['language'] = $language;
        }

        return $configuration->authorizationUrl.'/oauth2/auth?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Exchange the authorization code from the redirect for the first token pair.
     *
     * @throws InvalidGrantException when the code is invalid, expired or already used
     */
    public function exchangeAuthorizationCode(
        #[SensitiveParameter]
        string $code,
        string $redirectUri,
        #[SensitiveParameter]
        string $codeVerifier,
    ): Token {
        $configuration = $this->connector->configuration();

        return Token::fromArray($this->sendAndDecode(new ExchangeAuthorizationCodeRequest(
            clientId: $configuration->clientId,
            clientSecret: $configuration->clientSecret,
            authorizationCode: $code,
            redirectUri: $redirectUri,
            codeVerifier: $codeVerifier,
        )));
    }

    /**
     * Use the (single-use) refresh token to get a new access token and a new
     * refresh token. Persist the returned refresh token immediately.
     *
     * @throws InvalidGrantException when the refresh token was already used, expired or revoked
     */
    public function refresh(#[SensitiveParameter] string $refreshToken): Token
    {
        $configuration = $this->connector->configuration();

        return Token::fromArray($this->sendAndDecode(new RefreshAccessTokenRequest(
            clientId: $configuration->clientId,
            clientSecret: $configuration->clientSecret,
            refreshToken: $refreshToken,
        )));
    }

    /**
     * A client access token for the application-level endpoints (onboarding
     * details, usage, integration accounts and integration deletion).
     */
    public function clientCredentials(): Token
    {
        $configuration = $this->connector->configuration();

        return Token::fromArray($this->sendAndDecode(new RequestClientAccessTokenRequest(
            clientId: $configuration->clientId,
            clientSecret: $configuration->clientSecret,
        )));
    }

    /**
     * Revoke a refresh token, which removes the integration in Ponto.
     */
    public function revoke(#[SensitiveParameter] string $refreshToken): void
    {
        $configuration = $this->connector->configuration();

        $this->connector->send(new RevokeRefreshTokenRequest(
            clientId: $configuration->clientId,
            clientSecret: $configuration->clientSecret,
            refreshToken: $refreshToken,
        ));
    }
}
