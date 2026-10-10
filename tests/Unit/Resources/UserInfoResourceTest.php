<?php

namespace PontoConnect\Tests\Unit\Resources;

use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class UserInfoResourceTest extends TestCase
{
    public function test_get_returns_the_organization_behind_the_token(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('userinfo')]);
        $client = $this->mockedClient($mockClient);

        $userInfo = $client->userInfo->get();

        $this->assertSame('https://api.ibanity.com/ponto-connect/userinfo', $this->lastRequest($mockClient)->getUrl());
        $this->assertSame('3953d4ab-7ec4-4c0b-8b91-4be8626c0946', $userInfo->sub);
        $this->assertSame('Bytecard', $userInfo->name);
        $this->assertTrue($userInfo->onboardingComplete);
        $this->assertTrue($userInfo->paymentsActivated);
        $this->assertTrue($userInfo->paymentsActivationRequested);
        $this->assertTrue($userInfo->paymentRequestsActivated);
        $this->assertTrue($userInfo->paymentRequestsActivationRequested);
        $this->assertNull($userInfo->representativeOrganizationId);
        $this->assertFalse($userInfo->isRepresentative());
    }

    public function test_get_maps_representative_details(): void
    {
        $mockClient = new MockClient([MockResponse::make([
            'sub' => '3953d4ab-7ec4-4c0b-8b91-4be8626c0946',
            'name' => 'Bytecard',
            'representativeOrganizationId' => '11a67820-0712-4c93-90dc-5c0c897d07b8',
            'representativeOrganizationName' => 'Accounting Office',
        ])]);
        $client = $this->mockedClient($mockClient);

        $userInfo = $client->userInfo->get();

        $this->assertTrue($userInfo->isRepresentative());
        $this->assertSame('11a67820-0712-4c93-90dc-5c0c897d07b8', $userInfo->representativeOrganizationId);
        $this->assertSame('Accounting Office', $userInfo->representativeOrganizationName);
        $this->assertNull($userInfo->onboardingComplete);
    }
}
