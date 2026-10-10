<?php

namespace PontoConnect\Data;

/**
 * The organization behind an access token.
 *
 * `sub` is the Ponto organization id: store it right after the authorization
 * flow, it is needed for usage and integration deletion with a client token.
 * The other attributes are only filled when the `name` scope was granted.
 */
class UserInfo
{
    public function __construct(
        public readonly string $sub,
        public readonly ?string $name = null,
        public readonly ?bool $onboardingComplete = null,
        public readonly ?bool $paymentsActivated = null,
        public readonly ?bool $paymentsActivationRequested = null,
        public readonly ?bool $paymentRequestsActivated = null,
        public readonly ?bool $paymentRequestsActivationRequested = null,
        public readonly ?string $representativeOrganizationId = null,
        public readonly ?string $representativeOrganizationName = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sub: (string) ($data['sub'] ?? ''),
            name: self::nullableString($data['name'] ?? null),
            onboardingComplete: self::nullableBool($data['onboardingComplete'] ?? null),
            paymentsActivated: self::nullableBool($data['paymentsActivated'] ?? null),
            paymentsActivationRequested: self::nullableBool($data['paymentsActivationRequested'] ?? null),
            paymentRequestsActivated: self::nullableBool($data['paymentRequestsActivated'] ?? null),
            paymentRequestsActivationRequested: self::nullableBool($data['paymentRequestsActivationRequested'] ?? null),
            representativeOrganizationId: self::nullableString($data['representativeOrganizationId'] ?? null),
            representativeOrganizationName: self::nullableString($data['representativeOrganizationName'] ?? null),
        );
    }

    public function isRepresentative(): bool
    {
        return $this->representativeOrganizationId !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sub' => $this->sub,
            'name' => $this->name,
            'onboardingComplete' => $this->onboardingComplete,
            'paymentsActivated' => $this->paymentsActivated,
            'paymentsActivationRequested' => $this->paymentsActivationRequested,
            'paymentRequestsActivated' => $this->paymentRequestsActivated,
            'paymentRequestsActivationRequested' => $this->paymentRequestsActivationRequested,
            'representativeOrganizationId' => $this->representativeOrganizationId,
            'representativeOrganizationName' => $this->representativeOrganizationName,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        return (string) $value;
    }

    private static function nullableBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }
}
