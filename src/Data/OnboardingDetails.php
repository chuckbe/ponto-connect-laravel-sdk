<?php

namespace PontoConnect\Data;

use PontoConnect\Support\JsonApiResource;

/**
 * Prefilled sign-up details for the Ponto onboarding. The id must be passed
 * as `onboarding_details_id` in the authorization URL within five minutes.
 */
class OnboardingDetails
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $email = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $organizationName = null,
        public readonly ?string $organizationType = null,
        public readonly ?string $enterpriseNumber = null,
        public readonly ?string $vatNumber = null,
        public readonly ?string $addressStreetAddress = null,
        public readonly ?string $addressPostalCode = null,
        public readonly ?string $addressCity = null,
        public readonly ?string $addressCountry = null,
        public readonly ?string $partnerReference = null,
        public readonly ?string $initialFinancialInstitutionId = null,
        public readonly ?string $requestedOrganisationId = null,
        public readonly ?string $preferredOtpMethod = null,
        public readonly ?bool $automaticSubmissionOnCompletedForms = null,
        public readonly ?bool $skipFinancialInstitutionSelection = null,
    ) {
    }

    /**
     * @param array<string, mixed> $resource JSON:API resource object
     */
    public static function fromArray(array $resource): self
    {
        $details = JsonApiResource::fromArray($resource);

        return new self(
            id: (string) $details->id,
            email: $details->stringAttribute('email'),
            firstName: $details->stringAttribute('firstName'),
            lastName: $details->stringAttribute('lastName'),
            phoneNumber: $details->stringAttribute('phoneNumber'),
            organizationName: $details->stringAttribute('organizationName'),
            organizationType: $details->stringAttribute('organizationType'),
            enterpriseNumber: $details->stringAttribute('enterpriseNumber'),
            vatNumber: $details->stringAttribute('vatNumber'),
            addressStreetAddress: $details->stringAttribute('addressStreetAddress'),
            addressPostalCode: $details->stringAttribute('addressPostalCode'),
            addressCity: $details->stringAttribute('addressCity'),
            addressCountry: $details->stringAttribute('addressCountry'),
            partnerReference: $details->stringAttribute('partnerReference'),
            initialFinancialInstitutionId: $details->stringAttribute('initialFinancialInstitutionId'),
            requestedOrganisationId: $details->stringAttribute('requestedOrganisationId'),
            preferredOtpMethod: $details->stringAttribute('preferredOtpMethod'),
            automaticSubmissionOnCompletedForms: $details->boolAttribute('automaticSubmissionOnCompletedForms'),
            skipFinancialInstitutionSelection: $details->boolAttribute('skipFinancialInstitutionSelection'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phoneNumber' => $this->phoneNumber,
            'organizationName' => $this->organizationName,
            'organizationType' => $this->organizationType,
            'enterpriseNumber' => $this->enterpriseNumber,
            'vatNumber' => $this->vatNumber,
            'addressStreetAddress' => $this->addressStreetAddress,
            'addressPostalCode' => $this->addressPostalCode,
            'addressCity' => $this->addressCity,
            'addressCountry' => $this->addressCountry,
            'partnerReference' => $this->partnerReference,
            'initialFinancialInstitutionId' => $this->initialFinancialInstitutionId,
            'requestedOrganisationId' => $this->requestedOrganisationId,
            'preferredOtpMethod' => $this->preferredOtpMethod,
            'automaticSubmissionOnCompletedForms' => $this->automaticSubmissionOnCompletedForms,
            'skipFinancialInstitutionSelection' => $this->skipFinancialInstitutionSelection,
        ];
    }
}
