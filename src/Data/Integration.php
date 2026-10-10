<?php

namespace PontoConnect\Data;

use PontoConnect\Support\JsonApiResource;

class Integration
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $organizationId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $integration = JsonApiResource::fromArray($resource);

        return new self(
            id: (string) $integration->id,
            organizationId: $integration->relationshipId('organization'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organizationId' => $this->organizationId,
        ];
    }
}
