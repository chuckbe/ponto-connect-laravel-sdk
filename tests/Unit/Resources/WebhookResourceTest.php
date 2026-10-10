<?php

namespace PontoConnect\Tests\Unit\Resources;

use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

class WebhookResourceTest extends TestCase
{
    public function test_keys_returns_the_json_web_key_set(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('webhook-keys')]);
        $client = $this->mockedClient($mockClient, null);

        $jwks = $client->webhook->keys();

        $this->assertSame('https://api.ibanity.com/webhooks/keys', $this->lastRequest($mockClient)->getUrl());
        $this->assertCount(1, $jwks['keys']);
        $this->assertSame('dummy_1', $jwks['keys'][0]['kid']);
        $this->assertSame('RS512', $jwks['keys'][0]['alg']);
    }
}
