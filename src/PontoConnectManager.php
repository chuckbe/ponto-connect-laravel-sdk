<?php

namespace PontoConnect;

use PontoConnect\Api\PontoConnectApiClient;
use PontoConnect\Enums\Language;
use PontoConnect\Enums\Scope;
use PontoConnect\Webhooks\WebhookVerifier;
use SensitiveParameter;

/**
 * Entry point for the Laravel container and facade.
 *
 * Tokens belong to one organization, so the manager never keeps a token:
 * every call to api()/withAccessToken() returns a fresh connector.
 *
 * PontoConnect::api()->financialInstitution->list();
 * PontoConnect::withAccessToken($token)->account->list();
 */
class PontoConnectManager
{
    public function __construct(
        protected Configuration $configuration,
    ) {
    }

    /**
     * A connector, optionally authenticated with a customer or client access token.
     */
    public function api(#[SensitiveParameter] ?string $accessToken = null): PontoConnectApiClient
    {
        return new PontoConnectApiClient($this->configuration, $accessToken);
    }

    /**
     * A connector authenticated with the given access token.
     */
    public function withAccessToken(#[SensitiveParameter] string $accessToken): PontoConnectApiClient
    {
        return $this->api($accessToken);
    }

    /**
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
        return $this->api()->oauth->authorizationUrl(
            redirectUri: $redirectUri,
            state: $state,
            codeChallenge: $codeChallenge,
            scopes: $scopes,
            language: $language,
            onboardingDetailsId: $onboardingDetailsId,
        );
    }

    public function webhookVerifier(): WebhookVerifier
    {
        return new WebhookVerifier($this->configuration->applicationId);
    }

    public function configuration(): Configuration
    {
        return $this->configuration;
    }
}
