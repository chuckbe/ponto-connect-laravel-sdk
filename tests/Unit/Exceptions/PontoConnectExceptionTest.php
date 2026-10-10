<?php

namespace PontoConnect\Tests\Unit\Exceptions;

use PontoConnect\Exceptions\InvalidGrantException;
use PontoConnect\Exceptions\PontoConnectException;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class PontoConnectExceptionTest extends TestCase
{
    public function test_api_errors_are_mapped_with_status_codes_and_request_id(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->fixture('error'), 401, ['ibanity-request-id' => '00070A610440C8610A4306EB01BB5C7D3A551A43510']),
        ]);
        $client = $this->mockedClient($mockClient);

        try {
            $client->account->list();
            $this->fail('Expected a PontoConnectException.');
        } catch (PontoConnectException $exception) {
            $this->assertNotInstanceOf(InvalidGrantException::class, $exception);
            $this->assertSame(401, $exception->status);
            $this->assertSame(['invalidCredentials'], $exception->errorCodes());
            $this->assertTrue($exception->hasErrorCode('invalidCredentials'));
            $this->assertSame('Your credentials are invalid.', $exception->errors[0]['detail']);
            $this->assertSame('00070A610440C8610A4306EB01BB5C7D3A551A43510', $exception->requestId);
            $this->assertSame(
                'Ponto Connect request failed with status 401: invalidCredentials Your credentials are invalid. (ibanity-request-id: 00070A610440C8610A4306EB01BB5C7D3A551A43510)',
                $exception->getMessage(),
            );
        }
    }

    public function test_server_error_without_json_body_still_maps(): void
    {
        $mockClient = new MockClient([MockResponse::make('<html>Bad gateway</html>', 502)]);
        $client = $this->mockedClient($mockClient);

        try {
            $client->userInfo->get();
            $this->fail('Expected a PontoConnectException.');
        } catch (PontoConnectException $exception) {
            $this->assertSame(502, $exception->status);
            $this->assertSame([], $exception->errorCodes());
            $this->assertNull($exception->requestId);
        }
    }

    public function test_invalid_grant_in_json_api_format_is_also_recognised(): void
    {
        $mockClient = new MockClient([
            MockResponse::make(['errors' => [['code' => 'invalid_grant', 'detail' => 'Refresh token already used.']]], 400),
        ]);
        $client = $this->mockedClient($mockClient, null);

        $this->expectException(InvalidGrantException::class);

        $client->oauth->refresh('used-refresh-token');
    }

    public function test_other_token_errors_are_not_invalid_grant(): void
    {
        $mockClient = new MockClient([
            MockResponse::make(['error' => 'invalid_client', 'error_description' => 'Client authentication failed.'], 401),
        ]);
        $client = $this->mockedClient($mockClient, null);

        try {
            $client->oauth->clientCredentials();
            $this->fail('Expected a PontoConnectException.');
        } catch (PontoConnectException $exception) {
            $this->assertNotInstanceOf(InvalidGrantException::class, $exception);
            $this->assertSame(['invalid_client'], $exception->errorCodes());
        }
    }
}
