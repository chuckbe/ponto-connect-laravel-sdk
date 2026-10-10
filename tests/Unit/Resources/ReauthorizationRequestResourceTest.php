<?php

namespace PontoConnect\Tests\Unit\Resources;

use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

class ReauthorizationRequestResourceTest extends TestCase
{
    public function test_create_returns_the_redirect_link(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('reauthorization-request', 201)]);
        $client = $this->mockedClient($mockClient);

        $reauthorizationRequest = $client->reauthorizationRequest->create(
            '5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b',
            'https://fake-tpp.com/account-reauthorization-confirmation',
        );

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame(
            'https://api.ibanity.com/ponto-connect/accounts/5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b/reauthorization-requests',
            $pendingRequest->getUrl(),
        );
        $this->assertSame([
            'data' => [
                'type' => 'reauthorizationRequest',
                'attributes' => [
                    'redirectUri' => 'https://fake-tpp.com/account-reauthorization-confirmation',
                ],
            ],
        ], $pendingRequest->body()?->all());
        $this->assertSame('8a1bb459-e1de-47dc-8ffb-0925d8e341f3', $reauthorizationRequest->id);
        $this->assertSame(
            'https://authorize.acceptance.myponto.net/organizations/3953d4ab-7ec4-4c0b-8b91-4be8626c0946/sandbox/integrations/e1848b9c-9c72-431e-b0c0-410b0e579166/accounts/5a8820b5-87c5-4d8d-b0f1-32e710b0bd9b/reauthorization-requests/8a1bb459-e1de-47dc-8ffb-0925d8e341f3',
            $reauthorizationRequest->redirectUrl,
        );
    }
}
