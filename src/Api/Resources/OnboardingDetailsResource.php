<?php

namespace PontoConnect\Api\Resources;

use PontoConnect\Api\Requests\OnboardingDetails\CreateOnboardingDetailsRequest;
use PontoConnect\Data\OnboardingDetails;

/**
 * API reference, "Onboarding Details". Requires a client access token.
 */
class OnboardingDetailsResource extends Resource
{
    /**
     * Prefill the Ponto sign-up forms. Pass the returned id as
     * `onboardingDetailsId` to the authorization URL within five minutes.
     *
     * @param array{
     *     email: string,
     *     firstName?: string|null,
     *     lastName?: string|null,
     *     phoneNumber?: string|null,
     *     organizationName?: string|null,
     *     organizationType?: string|null,
     *     enterpriseNumber?: string|null,
     *     vatNumber?: string|null,
     *     addressStreetAddress?: string|null,
     *     addressPostalCode?: string|null,
     *     addressCity?: string|null,
     *     addressCountry?: string|null,
     *     partnerReference?: string|null,
     *     initialFinancialInstitutionId?: string|null,
     *     requestedOrganisationId?: string|null,
     *     automaticSubmissionOnCompletedForms?: bool|null,
     *     skipFinancialInstitutionSelection?: bool|null,
     * } $attributes
     */
    public function create(array $attributes, ?string $idempotencyKey = null): OnboardingDetails
    {
        return OnboardingDetails::fromArray(
            $this->sendAndDecodeData(new CreateOnboardingDetailsRequest($attributes, $idempotencyKey)),
        );
    }
}
