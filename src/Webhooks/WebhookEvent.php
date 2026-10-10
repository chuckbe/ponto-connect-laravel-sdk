<?php

namespace PontoConnect\Webhooks;

use DateTimeImmutable;
use PontoConnect\Enums\WebhookEventType;
use PontoConnect\Support\DateParser;
use PontoConnect\Support\JsonApiResource;

/**
 * A verified webhook notification. Payloads only carry ids: fetch the
 * related resources through the API to get the actual data.
 */
class WebhookEvent
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<string, string|null> $relationshipIds
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly array $attributes = [],
        public readonly array $relationshipIds = [],
        public readonly ?string $accountId = null,
        public readonly ?string $organizationId = null,
        public readonly ?string $synchronizationId = null,
        public readonly ?string $paymentRequestId = null,
        public readonly ?string $jti = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload the decoded webhook body (`{"data": {...}}`)
     * @param string|null $jti the unique id of the signature JWT
     */
    public static function fromPayload(array $payload, ?string $jti = null): self
    {
        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            $data = [];
        }

        $event = JsonApiResource::fromArray($data);

        return new self(
            id: (string) $event->id,
            type: (string) $event->type,
            createdAt: DateParser::parse($event->attribute('createdAt')),
            attributes: $event->attributes,
            relationshipIds: $event->relationshipIds,
            accountId: $event->relationshipId('account'),
            organizationId: $event->relationshipId('organization'),
            synchronizationId: $event->relationshipId('synchronization'),
            paymentRequestId: $event->relationshipId('paymentRequest'),
            jti: $jti,
        );
    }

    /**
     * The known event type, or null for an event type this SDK does not know yet.
     */
    public function eventType(): ?WebhookEventType
    {
        return WebhookEventType::tryFrom($this->type);
    }

    public function is(WebhookEventType $eventType): bool
    {
        return $this->type === $eventType->value;
    }

    /**
     * The `synchronizationSubtype` attribute of the synchronization events.
     */
    public function synchronizationSubtype(): ?string
    {
        $subtype = $this->attributes['synchronizationSubtype'] ?? null;
        if (! is_string($subtype)) {
            return null;
        }

        return $subtype;
    }

    /**
     * The `count` attribute of the (pending) transactions created/updated events.
     */
    public function count(): ?int
    {
        $count = $this->attributes['count'] ?? null;
        if (! is_numeric($count)) {
            return null;
        }

        return (int) $count;
    }
}
