<?php

namespace PontoConnect\Data;

use DateTimeImmutable;
use PontoConnect\Enums\SynchronizationStatus;
use PontoConnect\Support\DateParser;
use PontoConnect\Support\JsonApiResource;

class Synchronization
{
    /**
     * @param list<array<string, mixed>> $errors
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $status = null,
        public readonly ?string $subtype = null,
        public readonly ?string $resourceType = null,
        public readonly ?string $resourceId = null,
        public readonly array $errors = [],
        public readonly ?bool $customerOnline = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $synchronization = JsonApiResource::fromArray($resource);

        $errors = [];
        foreach ($synchronization->attribute('errors', []) as $error) {
            if (is_array($error)) {
                $errors[] = $error;
            }
        }

        return new self(
            id: (string) $synchronization->id,
            status: $synchronization->stringAttribute('status'),
            subtype: $synchronization->stringAttribute('subtype'),
            resourceType: $synchronization->stringAttribute('resourceType'),
            resourceId: $synchronization->stringAttribute('resourceId'),
            errors: $errors,
            customerOnline: $synchronization->boolAttribute('customerOnline'),
            createdAt: DateParser::parse($synchronization->attribute('createdAt')),
            updatedAt: DateParser::parse($synchronization->attribute('updatedAt')),
        );
    }

    public function isPending(): bool
    {
        return $this->status === SynchronizationStatus::PENDING->value;
    }

    public function isRunning(): bool
    {
        return $this->status === SynchronizationStatus::RUNNING->value;
    }

    public function isSuccessful(): bool
    {
        return $this->status === SynchronizationStatus::SUCCESS->value;
    }

    public function hasFailed(): bool
    {
        return $this->status === SynchronizationStatus::ERROR->value;
    }

    public function isFinished(): bool
    {
        return $this->isSuccessful() || $this->hasFailed();
    }

    /**
     * Error codes such as authorizationInvalid, authorizationRevoked,
     * authorizationExpired or technicalFailure.
     *
     * @return list<string>
     */
    public function errorCodes(): array
    {
        $codes = [];
        foreach ($this->errors as $error) {
            if (isset($error['code']) && is_string($error['code'])) {
                $codes[] = $error['code'];
            }
        }

        return $codes;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'subtype' => $this->subtype,
            'resourceType' => $this->resourceType,
            'resourceId' => $this->resourceId,
            'errors' => $this->errors,
            'customerOnline' => $this->customerOnline,
            'createdAt' => DateParser::format($this->createdAt),
            'updatedAt' => DateParser::format($this->updatedAt),
        ];
    }
}
