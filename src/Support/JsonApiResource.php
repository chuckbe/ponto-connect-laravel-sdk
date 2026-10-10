<?php

namespace PontoConnect\Support;

/**
 * Flattens a JSON:API resource object into id, type, attributes,
 * relationship ids, meta and links.
 */
final class JsonApiResource
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<string, string|null> $relationshipIds
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $links
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $type,
        public readonly array $attributes = [],
        public readonly array $relationshipIds = [],
        public readonly array $meta = [],
        public readonly array $links = [],
    ) {
    }

    /**
     * @param array<string, mixed> $resource
     */
    public static function fromArray(array $resource): self
    {
        $relationshipIds = [];
        foreach (self::arrayValue($resource, 'relationships') as $name => $relationship) {
            $relationshipId = null;
            if (is_array($relationship) && isset($relationship['data']['id'])) {
                $relationshipId = (string) $relationship['data']['id'];
            }

            $relationshipIds[(string) $name] = $relationshipId;
        }

        $id = null;
        if (isset($resource['id'])) {
            $id = (string) $resource['id'];
        }

        $type = null;
        if (isset($resource['type'])) {
            $type = (string) $resource['type'];
        }

        return new self(
            id: $id,
            type: $type,
            attributes: self::arrayValue($resource, 'attributes'),
            relationshipIds: $relationshipIds,
            meta: self::arrayValue($resource, 'meta'),
            links: self::arrayValue($resource, 'links'),
        );
    }

    public function relationshipId(string $relationship): ?string
    {
        return $this->relationshipIds[$relationship] ?? null;
    }

    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function stringAttribute(string $name): ?string
    {
        $value = $this->attributes[$name] ?? null;
        if ($value === null || is_array($value)) {
            return null;
        }

        return (string) $value;
    }

    public function boolAttribute(string $name): ?bool
    {
        $value = $this->attributes[$name] ?? null;
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }

    /**
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private static function arrayValue(array $source, string $key): array
    {
        if (isset($source[$key]) && is_array($source[$key])) {
            return $source[$key];
        }

        return [];
    }
}
