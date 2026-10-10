<?php

namespace PontoConnect\Tests\Unit\Resources;

use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

class OnboardingDetailsResourceTest extends TestCase
{
    public function test_create_posts_the_onboarding_details(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('onboarding-details', 201)]);
        $client = $this->mockedClient($mockClient, 'client-access-token');

        $onboardingDetails = $client->onboardingDetails->create([
            'email' => 'jsmith@example.com',
            'firstName' => 'Jo',
            'lastName' => 'Smith',
            'organizationName' => 'Smith Ltd',
            'vatNumber' => 'BE0999999999',
            'phoneNumber' => null,
            'skipFinancialInstitutionSelection' => false,
        ], 'a6d9d4cc-2c61-4c4b-9a9e-3e1c6d0b7a10');

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('https://api.ibanity.com/ponto-connect/onboarding-details', $pendingRequest->getUrl());
        $this->assertSame('Bearer client-access-token', $pendingRequest->headers()->get('Authorization'));
        $this->assertSame('a6d9d4cc-2c61-4c4b-9a9e-3e1c6d0b7a10', $pendingRequest->headers()->get('Ibanity-Idempotency-Key'));
        $this->assertSame([
            'data' => [
                'type' => 'onboardingDetails',
                'attributes' => [
                    'email' => 'jsmith@example.com',
                    'firstName' => 'Jo',
                    'lastName' => 'Smith',
                    'organizationName' => 'Smith Ltd',
                    'vatNumber' => 'BE0999999999',
                    'skipFinancialInstitutionSelection' => false,
                ],
            ],
        ], $pendingRequest->body()?->all());

        $this->assertSame('f56b519d-14d6-4ce9-adbf-2fce805318c6', $onboardingDetails->id);
        $this->assertSame('jsmith@example.com', $onboardingDetails->email);
        $this->assertSame('business', $onboardingDetails->organizationType);
        $this->assertSame('0999999999', $onboardingDetails->enterpriseNumber);
        $this->assertSame('123 Main St', $onboardingDetails->addressStreetAddress);
        $this->assertSame('1000', $onboardingDetails->addressPostalCode);
        $this->assertSame('Brussels', $onboardingDetails->addressCity);
        $this->assertSame('BE', $onboardingDetails->addressCountry);
        $this->assertSame('partner-reference', $onboardingDetails->partnerReference);
        $this->assertSame('69f8d721-0c12-4a12-8496-943e2dc5a927', $onboardingDetails->initialFinancialInstitutionId);
        $this->assertSame('69f8d721-0c12-4a12-8496-943e2dc5a927', $onboardingDetails->requestedOrganisationId);
        $this->assertNull($onboardingDetails->preferredOtpMethod);
        $this->assertTrue($onboardingDetails->automaticSubmissionOnCompletedForms);
        $this->assertFalse($onboardingDetails->skipFinancialInstitutionSelection);
    }
}
