<?php

namespace PontoConnect\Data;

use DateTimeImmutable;
use PontoConnect\Support\DateParser;
use PontoConnect\Support\JsonApiResource;

class FinancialInstitution
{
    /**
     * @param list<string> $paymentsProductTypes
     * @param list<string> $bulkPaymentsProductTypes
     * @param list<string> $periodicPaymentsProductTypes
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $name = null,
        public readonly ?string $status = null,
        public readonly bool $deprecated = false,
        public readonly ?string $country = null,
        public readonly ?string $bic = null,
        public readonly ?string $logoUrl = null,
        public readonly ?string $timeZone = null,
        public readonly bool $pendingTransactionsAvailable = false,
        public readonly bool $paymentsEnabled = false,
        public readonly bool $bulkPaymentsEnabled = false,
        public readonly bool $periodicPaymentsEnabled = false,
        public readonly bool $futureDatedPaymentsAllowed = false,
        public readonly array $paymentsProductTypes = [],
        public readonly array $bulkPaymentsProductTypes = [],
        public readonly array $periodicPaymentsProductTypes = [],
        public readonly ?string $primaryColor = null,
        public readonly ?string $secondaryColor = null,
        public readonly ?string $sharedBrandName = null,
        public readonly ?string $sharedBrandReference = null,
        public readonly ?DateTimeImmutable $maintenanceFrom = null,
        public readonly ?DateTimeImmutable $maintenanceTo = null,
        public readonly ?string $maintenanceType = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $institution = JsonApiResource::fromArray($resource);

        return new self(
            id: (string) $institution->id,
            name: $institution->stringAttribute('name'),
            status: $institution->stringAttribute('status'),
            deprecated: (bool) $institution->attribute('deprecated', false),
            country: $institution->stringAttribute('country'),
            bic: $institution->stringAttribute('bic'),
            logoUrl: $institution->stringAttribute('logoUrl'),
            timeZone: $institution->stringAttribute('timeZone'),
            pendingTransactionsAvailable: (bool) $institution->attribute('pendingTransactionsAvailable', false),
            paymentsEnabled: (bool) $institution->attribute('paymentsEnabled', false),
            bulkPaymentsEnabled: (bool) $institution->attribute('bulkPaymentsEnabled', false),
            periodicPaymentsEnabled: (bool) $institution->attribute('periodicPaymentsEnabled', false),
            futureDatedPaymentsAllowed: (bool) $institution->attribute('futureDatedPaymentsAllowed', false),
            paymentsProductTypes: self::stringList($institution->attribute('paymentsProductTypes', [])),
            bulkPaymentsProductTypes: self::stringList($institution->attribute('bulkPaymentsProductTypes', [])),
            periodicPaymentsProductTypes: self::stringList($institution->attribute('periodicPaymentsProductTypes', [])),
            primaryColor: $institution->stringAttribute('primaryColor'),
            secondaryColor: $institution->stringAttribute('secondaryColor'),
            sharedBrandName: $institution->stringAttribute('sharedBrandName'),
            sharedBrandReference: $institution->stringAttribute('sharedBrandReference'),
            maintenanceFrom: DateParser::parse($institution->attribute('maintenanceFrom')),
            maintenanceTo: DateParser::parse($institution->attribute('maintenanceTo')),
            maintenanceType: $institution->stringAttribute('maintenanceType'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'deprecated' => $this->deprecated,
            'country' => $this->country,
            'bic' => $this->bic,
            'logoUrl' => $this->logoUrl,
            'timeZone' => $this->timeZone,
            'pendingTransactionsAvailable' => $this->pendingTransactionsAvailable,
            'paymentsEnabled' => $this->paymentsEnabled,
            'bulkPaymentsEnabled' => $this->bulkPaymentsEnabled,
            'periodicPaymentsEnabled' => $this->periodicPaymentsEnabled,
            'futureDatedPaymentsAllowed' => $this->futureDatedPaymentsAllowed,
            'paymentsProductTypes' => $this->paymentsProductTypes,
            'bulkPaymentsProductTypes' => $this->bulkPaymentsProductTypes,
            'periodicPaymentsProductTypes' => $this->periodicPaymentsProductTypes,
            'primaryColor' => $this->primaryColor,
            'secondaryColor' => $this->secondaryColor,
            'sharedBrandName' => $this->sharedBrandName,
            'sharedBrandReference' => $this->sharedBrandReference,
            'maintenanceFrom' => DateParser::format($this->maintenanceFrom),
            'maintenanceTo' => DateParser::format($this->maintenanceTo),
            'maintenanceType' => $this->maintenanceType,
        ];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_map('strval', $values));
    }
}
