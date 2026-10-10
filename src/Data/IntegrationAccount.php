<?php

namespace PontoConnect\Data;

use DateTimeImmutable;
use PontoConnect\Support\DateParser;
use PontoConnect\Support\JsonApiResource;

/**
 * The link between a customer's account and an integration with your application.
 */
class IntegrationAccount
{
    public function __construct(
        public readonly string $id,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?DateTimeImmutable $lastAccessedAt = null,
        public readonly ?string $partnerReference = null,
        public readonly ?string $organizationId = null,
        public readonly ?string $accountId = null,
        public readonly ?string $financialInstitutionId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $integrationAccount = JsonApiResource::fromArray($resource);

        return new self(
            id: (string) $integrationAccount->id,
            createdAt: DateParser::parse($integrationAccount->attribute('createdAt')),
            lastAccessedAt: DateParser::parse($integrationAccount->attribute('lastAccessedAt')),
            partnerReference: $integrationAccount->stringAttribute('partnerReference'),
            organizationId: $integrationAccount->relationshipId('organization'),
            accountId: $integrationAccount->relationshipId('account'),
            financialInstitutionId: $integrationAccount->relationshipId('financialInstitution'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'createdAt' => DateParser::format($this->createdAt),
            'lastAccessedAt' => DateParser::format($this->lastAccessedAt),
            'partnerReference' => $this->partnerReference,
            'organizationId' => $this->organizationId,
            'accountId' => $this->accountId,
            'financialInstitutionId' => $this->financialInstitutionId,
        ];
    }
}
