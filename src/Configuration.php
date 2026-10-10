<?php

namespace PontoConnect;

use InvalidArgumentException;
use PontoConnect\Enums\Environment;
use SensitiveParameter;

/**
 * Immutable settings for one Ponto Connect application.
 */
final class Configuration
{
    public const DEFAULT_API_URL = 'https://api.ibanity.com';

    public const DEFAULT_TIMEOUT = 30;

    public const DEFAULT_SCOPES = ['ai', 'pi', 'name', 'offline_access'];

    public readonly string $applicationId;

    public readonly string $authorizationUrl;

    /**
     * @var list<string>
     */
    public readonly array $scopes;

    /**
     * @param list<string>|null $scopes
     */
    public function __construct(
        public readonly Environment $environment,
        public readonly string $clientId,
        #[SensitiveParameter]
        public readonly string $clientSecret,
        ?string $applicationId = null,
        public readonly ?string $certificatePath = null,
        public readonly ?string $privateKeyPath = null,
        #[SensitiveParameter]
        public readonly ?string $privateKeyPassphrase = null,
        public readonly ?string $caChainPath = null,
        public readonly string $apiUrl = self::DEFAULT_API_URL,
        public readonly int $timeout = self::DEFAULT_TIMEOUT,
        ?array $scopes = null,
    ) {
        $this->applicationId = self::nullIfBlank($applicationId) ?? $clientId;
        $this->authorizationUrl = $environment->authorizationUrl();
        $this->scopes = $scopes ?? self::DEFAULT_SCOPES;
    }

    /**
     * Build the configuration from the `ponto-connect` config array.
     *
     * @param array{
     *     environment?: string|Environment|null,
     *     client_id?: string|null,
     *     client_secret?: string|null,
     *     application_id?: string|null,
     *     certificate_path?: string|null,
     *     private_key_path?: string|null,
     *     private_key_passphrase?: string|null,
     *     ca_chain_path?: string|null,
     *     api_url?: string|null,
     *     timeout?: int|string|null,
     *     scopes?: string|list<string>|null,
     * } $config
     */
    public static function fromArray(#[SensitiveParameter] array $config): self
    {
        $environment = $config['environment'] ?? Environment::SANDBOX;
        if (! $environment instanceof Environment) {
            $environment = Environment::tryFrom((string) $environment)
                ?? throw new InvalidArgumentException(sprintf(
                    'Invalid Ponto Connect environment "%s", expected "sandbox" or "live".',
                    $environment,
                ));
        }

        $timeout = self::DEFAULT_TIMEOUT;
        if (isset($config['timeout']) && is_numeric($config['timeout'])) {
            $timeout = (int) $config['timeout'];
        }

        $scopes = null;
        if (isset($config['scopes'])) {
            $scopes = self::parseScopes($config['scopes']);
        }

        return new self(
            environment: $environment,
            clientId: (string) ($config['client_id'] ?? ''),
            clientSecret: (string) ($config['client_secret'] ?? ''),
            applicationId: $config['application_id'] ?? null,
            certificatePath: self::nullIfBlank($config['certificate_path'] ?? null),
            privateKeyPath: self::nullIfBlank($config['private_key_path'] ?? null),
            privateKeyPassphrase: self::nullIfBlank($config['private_key_passphrase'] ?? null),
            caChainPath: self::nullIfBlank($config['ca_chain_path'] ?? null),
            apiUrl: self::nullIfBlank($config['api_url'] ?? null) ?? self::DEFAULT_API_URL,
            timeout: $timeout,
            scopes: $scopes,
        );
    }

    public function isSandbox(): bool
    {
        return $this->environment === Environment::SANDBOX;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'environment' => $this->environment,
            'clientId' => $this->clientId,
            'clientSecret' => '********',
            'applicationId' => $this->applicationId,
            'certificatePath' => $this->certificatePath,
            'privateKeyPath' => $this->privateKeyPath,
            'privateKeyPassphrase' => $this->privateKeyPassphrase === null ? null : '********',
            'caChainPath' => $this->caChainPath,
            'apiUrl' => $this->apiUrl,
            'authorizationUrl' => $this->authorizationUrl,
            'timeout' => $this->timeout,
            'scopes' => $this->scopes,
        ];
    }

    /**
     * @param string|list<string> $scopes
     *
     * @return list<string>
     */
    private static function parseScopes(string|array $scopes): array
    {
        if (is_string($scopes)) {
            $scopes = preg_split('/[\s,]+/', $scopes) ?: [];
        }

        return array_values(array_filter(
            array_map('trim', $scopes),
            fn (string $scope) => $scope !== '',
        ));
    }

    private static function nullIfBlank(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
