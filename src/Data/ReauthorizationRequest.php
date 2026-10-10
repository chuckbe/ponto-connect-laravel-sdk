<?php

namespace PontoConnect\Data;

use PontoConnect\Support\JsonApiResource;

class ReauthorizationRequest
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $redirectUrl = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $reauthorizationRequest = JsonApiResource::fromArray($resource);

        $redirectUrl = null;
        if (isset($reauthorizationRequest->links['redirect'])) {
            $redirectUrl = (string) $reauthorizationRequest->links['redirect'];
        }

        return new self(
            id: (string) $reauthorizationRequest->id,
            redirectUrl: $redirectUrl,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'redirectUrl' => $this->redirectUrl,
        ];
    }
}
