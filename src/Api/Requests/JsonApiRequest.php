<?php

namespace PontoConnect\Api\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Base class for POST/PATCH requests with a JSON:API body
 * (`{"data": {"type": ..., "attributes": {...}}}`).
 *
 * Sends the optional idempotency key as `Ibanity-Idempotency-Key`, so a
 * request can safely be retried without being processed twice.
 */
abstract class JsonApiRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected ?string $idempotencyKey = null;

    /**
     * The JSON:API resource type, e.g. "synchronization".
     */
    abstract protected function resourceType(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function attributes(): array;

    public function idempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/vnd.api+json',
        ];

        if ($this->idempotencyKey !== null) {
            $headers['Ibanity-Idempotency-Key'] = $this->idempotencyKey;
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'data' => [
                'type' => $this->resourceType(),
                'attributes' => $this->attributes(),
            ],
        ];
    }
}
