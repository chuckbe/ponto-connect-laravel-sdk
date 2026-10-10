<?php

namespace PontoConnect\Api;

use PontoConnect\Api\Resources\AccountResource;
use PontoConnect\Api\Resources\FinancialInstitutionResource;
use PontoConnect\Api\Resources\IntegrationAccountResource;
use PontoConnect\Api\Resources\IntegrationResource;
use PontoConnect\Api\Resources\OAuthResource;
use PontoConnect\Api\Resources\OnboardingDetailsResource;
use PontoConnect\Api\Resources\PendingTransactionResource;
use PontoConnect\Api\Resources\ReauthorizationRequestResource;
use PontoConnect\Api\Resources\SandboxResource;
use PontoConnect\Api\Resources\SynchronizationResource;
use PontoConnect\Api\Resources\TransactionResource;
use PontoConnect\Api\Resources\UsageResource;
use PontoConnect\Api\Resources\UserInfoResource;
use PontoConnect\Api\Resources\WebhookResource;
use PontoConnect\Configuration;
use PontoConnect\Exceptions\PontoConnectException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use SensitiveParameter;
use Throwable;

/**
 * Saloon connector for the Ponto Connect and Webhooks APIs.
 *
 * Every request is sent over mutual TLS with the application certificate.
 * Pass a customer (or client) access token to call the endpoints that
 * require a Bearer token; the token endpoints use Basic auth instead.
 */
class PontoConnectApiClient extends Connector
{
    use AlwaysThrowOnErrors;

    public OAuthResource $oauth;

    public UserInfoResource $userInfo;

    public OnboardingDetailsResource $onboardingDetails;

    public FinancialInstitutionResource $financialInstitution;

    public AccountResource $account;

    public TransactionResource $transaction;

    public PendingTransactionResource $pendingTransaction;

    public SynchronizationResource $synchronization;

    public ReauthorizationRequestResource $reauthorizationRequest;

    public IntegrationAccountResource $integrationAccount;

    public UsageResource $usage;

    public IntegrationResource $integration;

    public SandboxResource $sandbox;

    public WebhookResource $webhook;

    public function __construct(
        protected Configuration $configuration,
        #[SensitiveParameter]
        protected ?string $accessToken = null,
    ) {
        $this->oauth = new OAuthResource($this);
        $this->userInfo = new UserInfoResource($this);
        $this->onboardingDetails = new OnboardingDetailsResource($this);
        $this->financialInstitution = new FinancialInstitutionResource($this);
        $this->account = new AccountResource($this);
        $this->transaction = new TransactionResource($this);
        $this->pendingTransaction = new PendingTransactionResource($this);
        $this->synchronization = new SynchronizationResource($this);
        $this->reauthorizationRequest = new ReauthorizationRequestResource($this);
        $this->integrationAccount = new IntegrationAccountResource($this);
        $this->usage = new UsageResource($this);
        $this->integration = new IntegrationResource($this);
        $this->sandbox = new SandboxResource($this);
        $this->webhook = new WebhookResource($this);
    }

    public function configuration(): Configuration
    {
        return $this->configuration;
    }

    public function resolveBaseUrl(): string
    {
        return rtrim($this->configuration->apiUrl, '/');
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return PontoConnectException::fromResponse($response, $senderException);
    }

    protected function defaultAuth(): ?TokenAuthenticator
    {
        if ($this->accessToken === null) {
            return null;
        }

        return new TokenAuthenticator($this->accessToken);
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.api+json',
        ];
    }

    /**
     * Guzzle request options: the mutual TLS client certificate and key,
     * the optional CA chain to verify the server and the timeout.
     *
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        $config = [
            'timeout' => $this->configuration->timeout,
            'verify' => $this->configuration->caChainPath ?? true,
        ];

        if ($this->configuration->certificatePath !== null) {
            $config['cert'] = $this->withPassphrase($this->configuration->certificatePath);
        }

        if ($this->configuration->privateKeyPath !== null) {
            $config['ssl_key'] = $this->withPassphrase($this->configuration->privateKeyPath);
        }

        return $config;
    }

    /**
     * @return string|array{0: string, 1: string}
     */
    private function withPassphrase(string $path): string|array
    {
        if ($this->configuration->privateKeyPassphrase === null) {
            return $path;
        }

        return [$path, $this->configuration->privateKeyPassphrase];
    }
}
