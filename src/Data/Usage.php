<?php

namespace PontoConnect\Data;

use PontoConnect\Support\Decimal;
use PontoConnect\Support\JsonApiResource;

/**
 * Billable usage of an organization's integration for one month.
 *
 * `accountCount` is prorated over the month, so it is a decimal string
 * (e.g. "1.5" when an account was linked halfway through the month).
 */
class Usage
{
    public function __construct(
        public readonly string $month,
        public readonly ?string $organizationId = null,
        public readonly ?string $accountCount = null,
        public readonly ?int $totalAccounts = null,
        public readonly ?int $paymentCount = null,
        public readonly ?int $bulkPaymentCount = null,
        public readonly ?int $bulkPaymentBundleCount = null,
        public readonly ?int $paymentAccountCount = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $usage = JsonApiResource::fromArray($resource);

        return new self(
            month: (string) $usage->id,
            organizationId: $usage->relationshipId('organization'),
            accountCount: Decimal::fromNumber($usage->attribute('accountCount'), minimumScale: 0),
            totalAccounts: self::nullableInt($usage->attribute('totalAccounts')),
            paymentCount: self::nullableInt($usage->attribute('paymentCount')),
            bulkPaymentCount: self::nullableInt($usage->attribute('bulkPaymentCount')),
            bulkPaymentBundleCount: self::nullableInt($usage->attribute('bulkPaymentBundleCount')),
            paymentAccountCount: self::nullableInt($usage->attribute('paymentAccountCount')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'month' => $this->month,
            'organizationId' => $this->organizationId,
            'accountCount' => $this->accountCount,
            'totalAccounts' => $this->totalAccounts,
            'paymentCount' => $this->paymentCount,
            'bulkPaymentCount' => $this->bulkPaymentCount,
            'bulkPaymentBundleCount' => $this->bulkPaymentBundleCount,
            'paymentAccountCount' => $this->paymentAccountCount,
        ];
    }

    private static function nullableInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
