<?php

namespace PontoConnect\Api\Requests\OnboardingDetails;

use PontoConnect\Api\Requests\JsonApiRequest;
use Saloon\Enums\Method;

/**
 * Create Onboarding Details: POST /ponto-connect/onboarding-details (client access token).
 */
class CreateOnboardingDetailsRequest extends JsonApiRequest
{
    protected Method $method = Method::POST;

    /**
     * @param array<string, string|bool|null> $onboardingAttributes
     */
    public function __construct(
        protected array $onboardingAttributes,
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = $idempotencyKey;
    }

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/onboarding-details';
    }

    protected function resourceType(): string
    {
        return 'onboardingDetails';
    }

    /**
     * @return array<string, string|bool>
     */
    protected function attributes(): array
    {
        return array_filter(
            $this->onboardingAttributes,
            fn (string|bool|null $value) => $value !== null,
        );
    }
}
