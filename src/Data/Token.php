<?php

namespace PontoConnect\Data;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use SensitiveParameter;

/**
 * An OAuth2 token response.
 *
 * Refresh tokens are single-use: persist `refreshToken` immediately after
 * every refresh, the previous one is invalid from that moment on.
 */
class Token
{
    public function __construct(
        #[SensitiveParameter]
        public readonly string $accessToken,
        public readonly int $expiresIn,
        public readonly DateTimeImmutable $expiresAt,
        #[SensitiveParameter]
        public readonly ?string $refreshToken = null,
        public readonly string $scope = '',
        public readonly string $tokenType = 'bearer',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(#[SensitiveParameter] array $data, ?DateTimeImmutable $issuedAt = null): self
    {
        $expiresIn = (int) ($data['expires_in'] ?? 0);
        $issuedAt ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $refreshToken = null;
        if (isset($data['refresh_token']) && $data['refresh_token'] !== '') {
            $refreshToken = (string) $data['refresh_token'];
        }

        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            expiresIn: $expiresIn,
            expiresAt: $issuedAt->add(new DateInterval('PT'.max(0, $expiresIn).'S')),
            refreshToken: $refreshToken,
            scope: (string) ($data['scope'] ?? ''),
            tokenType: (string) ($data['token_type'] ?? 'bearer'),
        );
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return array_values(array_filter(explode(' ', $this->scope)));
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes(), true);
    }

    public function isExpired(DateTimeImmutable $moment, int $marginInSeconds = 30): bool
    {
        return $moment->add(new DateInterval('PT'.max(0, $marginInSeconds).'S')) >= $this->expiresAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
            'refresh_token' => $this->refreshToken,
            'scope' => $this->scope,
            'token_type' => $this->tokenType,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'accessToken' => '********',
            'expiresIn' => $this->expiresIn,
            'expiresAt' => $this->expiresAt,
            'refreshToken' => $this->refreshToken === null ? null : '********',
            'scope' => $this->scope,
            'tokenType' => $this->tokenType,
        ];
    }
}
