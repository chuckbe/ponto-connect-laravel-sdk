<?php

namespace PontoConnect\Data;

use DateTimeImmutable;
use PontoConnect\Support\DateParser;
use PontoConnect\Support\Decimal;
use PontoConnect\Support\JsonApiResource;

/**
 * A bank account the customer added to the integration.
 *
 * Balances are decimal strings, see {@see Decimal}.
 */
class Account
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
        public readonly ?string $internalReference = null,
        public readonly bool $deprecated = false,
        public readonly ?string $currentBalance = null,
        public readonly ?DateTimeImmutable $currentBalanceChangedAt = null,
        public readonly ?DateTimeImmutable $currentBalanceReferenceDate = null,
        public readonly ?DateTimeImmutable $currentBalanceVariationObservedAt = null,
        public readonly ?string $availableBalance = null,
        public readonly ?DateTimeImmutable $availableBalanceChangedAt = null,
        public readonly ?DateTimeImmutable $availableBalanceReferenceDate = null,
        public readonly ?DateTimeImmutable $availableBalanceVariationObservedAt = null,
        public readonly ?DateTimeImmutable $authorizedAt = null,
        public readonly ?DateTimeImmutable $authorizationExpirationExpectedAt = null,
        public readonly ?string $availability = null,
        public readonly ?DateTimeImmutable $synchronizedAt = null,
        public readonly ?Synchronization $latestSynchronization = null,
        public readonly ?string $financialInstitutionId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $account = JsonApiResource::fromArray($resource);

        $latestSynchronization = null;
        if (isset($account->meta['latestSynchronization']) && is_array($account->meta['latestSynchronization'])) {
            $latestSynchronization = Synchronization::fromArray($account->meta['latestSynchronization']);
        }

        $availability = null;
        if (isset($account->meta['availability'])) {
            $availability = (string) $account->meta['availability'];
        }

        return new self(
            id: (string) $account->id,
            reference: $account->stringAttribute('reference'),
            referenceType: $account->stringAttribute('referenceType'),
            currency: $account->stringAttribute('currency'),
            holderName: $account->stringAttribute('holderName'),
            description: $account->stringAttribute('description'),
            product: $account->stringAttribute('product'),
            subtype: $account->stringAttribute('subtype'),
            internalReference: $account->stringAttribute('internalReference'),
            deprecated: (bool) $account->attribute('deprecated', false),
            currentBalance: Decimal::fromNumber($account->attribute('currentBalance')),
            currentBalanceChangedAt: DateParser::parse($account->attribute('currentBalanceChangedAt')),
            currentBalanceReferenceDate: DateParser::parse($account->attribute('currentBalanceReferenceDate')),
            currentBalanceVariationObservedAt: DateParser::parse($account->attribute('currentBalanceVariationObservedAt')),
            availableBalance: Decimal::fromNumber($account->attribute('availableBalance')),
            availableBalanceChangedAt: DateParser::parse($account->attribute('availableBalanceChangedAt')),
            availableBalanceReferenceDate: DateParser::parse($account->attribute('availableBalanceReferenceDate')),
            availableBalanceVariationObservedAt: DateParser::parse($account->attribute('availableBalanceVariationObservedAt')),
            authorizedAt: DateParser::parse($account->attribute('authorizedAt')),
            authorizationExpirationExpectedAt: DateParser::parse($account->attribute('authorizationExpirationExpectedAt')),
            availability: $availability,
            synchronizedAt: DateParser::parse($account->meta['synchronizedAt'] ?? null),
            latestSynchronization: $latestSynchronization,
            financialInstitutionId: $account->relationshipId('financialInstitution'),
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
            'internalReference' => $this->internalReference,
            'deprecated' => $this->deprecated,
            'currentBalance' => $this->currentBalance,
            'currentBalanceChangedAt' => DateParser::format($this->currentBalanceChangedAt),
            'currentBalanceReferenceDate' => DateParser::format($this->currentBalanceReferenceDate),
            'currentBalanceVariationObservedAt' => DateParser::format($this->currentBalanceVariationObservedAt),
            'availableBalance' => $this->availableBalance,
            'availableBalanceChangedAt' => DateParser::format($this->availableBalanceChangedAt),
            'availableBalanceReferenceDate' => DateParser::format($this->availableBalanceReferenceDate),
            'availableBalanceVariationObservedAt' => DateParser::format($this->availableBalanceVariationObservedAt),
            'authorizedAt' => DateParser::format($this->authorizedAt),
            'authorizationExpirationExpectedAt' => DateParser::format($this->authorizationExpirationExpectedAt),
            'availability' => $this->availability,
            'synchronizedAt' => DateParser::format($this->synchronizedAt),
            'latestSynchronization' => $this->latestSynchronization?->toArray(),
            'financialInstitutionId' => $this->financialInstitutionId,
        ];
    }
}
