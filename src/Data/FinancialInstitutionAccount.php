<?php

namespace PontoConnect\Data;

use DateTimeImmutable;
use PontoConnect\Support\DateParser;
use PontoConnect\Support\Decimal;
use PontoConnect\Support\JsonApiResource;

/**
 * A fake bank account in the sandbox.
 */
class FinancialInstitutionAccount
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $reference = null,
        public readonly ?string $referenceType = null,
        public readonly ?string $currency = null,
        public readonly ?string $holderName = null,
        public readonly ?string $description = null,
        public readonly ?string $product = null,
        public readonly ?string $subtype = null,
        public readonly ?string $currentBalance = null,
        public readonly ?DateTimeImmutable $currentBalanceChangedAt = null,
        public readonly ?DateTimeImmutable $currentBalanceReferenceDate = null,
        public readonly ?string $availableBalance = null,
        public readonly ?DateTimeImmutable $availableBalanceChangedAt = null,
        public readonly ?DateTimeImmutable $availableBalanceReferenceDate = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $account = JsonApiResource::fromArray($resource);

        return new self(
            id: (string) $account->id,
            reference: $account->stringAttribute('reference'),
            referenceType: $account->stringAttribute('referenceType'),
            currency: $account->stringAttribute('currency'),
            holderName: $account->stringAttribute('holderName'),
            description: $account->stringAttribute('description'),
            product: $account->stringAttribute('product'),
            subtype: $account->stringAttribute('subtype'),
            currentBalance: Decimal::fromNumber($account->attribute('currentBalance')),
            currentBalanceChangedAt: DateParser::parse($account->attribute('currentBalanceChangedAt')),
            currentBalanceReferenceDate: DateParser::parse($account->attribute('currentBalanceReferenceDate')),
            availableBalance: Decimal::fromNumber($account->attribute('availableBalance')),
            availableBalanceChangedAt: DateParser::parse($account->attribute('availableBalanceChangedAt')),
            availableBalanceReferenceDate: DateParser::parse($account->attribute('availableBalanceReferenceDate')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'referenceType' => $this->referenceType,
            'currency' => $this->currency,
            'holderName' => $this->holderName,
            'description' => $this->description,
            'product' => $this->product,
            'subtype' => $this->subtype,
            'currentBalance' => $this->currentBalance,
            'currentBalanceChangedAt' => DateParser::format($this->currentBalanceChangedAt),
            'currentBalanceReferenceDate' => DateParser::format($this->currentBalanceReferenceDate),
            'availableBalance' => $this->availableBalance,
            'availableBalanceChangedAt' => DateParser::format($this->availableBalanceChangedAt),
            'availableBalanceReferenceDate' => DateParser::format($this->availableBalanceReferenceDate),
        ];
    }
}
