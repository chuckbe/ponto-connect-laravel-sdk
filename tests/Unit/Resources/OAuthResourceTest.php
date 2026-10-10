<?php

namespace PontoConnect\Tests\Unit\Resources;

use PontoConnect\Data\Token;
use PontoConnect\Enums\Language;
use PontoConnect\Enums\Scope;
use PontoConnect\Exceptions\InvalidGrantException;
use PontoConnect\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class OAuthResourceTest extends TestCase
{
    public function test_authorization_url_contains_pkce_challenge_and_default_scopes(): void
    {
        $client = $this->mockedClient(new MockClient([]), null);

        $url = $client->oauth->authorizationUrl(
            redirectUri: 'https://app.chuck.be/ponto/callback',
            state: 'state-123',
            codeChallenge: 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
        );

        $this->assertStringStartsWith('https://sandbox-authorization.myponto.com/oauth2/auth?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame([
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => 'https://app.chuck.be/ponto/callback',
            'response_type' => 'code',
            'scope' => 'ai pi name offline_access',
            'state' => 'state-123',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256',
        ], $query);
        $this->assertStringContainsString('scope=ai%20pi%20name%20offline_access', $url);
    }

    public function test_authorization_url_accepts_scopes_language_and_onboarding_details(): void
    {
        $client = $this->mockedClient(new MockClient([]), null);

        $url = $client->oauth->authorizationUrl(
            redirectUri: 'https://app.chuck.be/ponto/callback',
            state: 'state-123',
            codeChallenge: 'challenge',
            scopes: [Scope::ACCOUNT_INFORMATION, 'name', Scope::OFFLINE_ACCESS],
            language: Language::DUTCH,
            onboardingDetailsId: 'f56b519d-14d6-4ce9-adbf-2fce805318c6',
        );

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('ai name offline_access', $query['scope']);
        $this->assertSame('nl', $query['language']);
        $this->assertSame('f56b519d-14d6-4ce9-adbf-2fce805318c6', $query['onboarding_details_id']);
    }

    public function test_exchange_authorization_code_uses_basic_auth_and_form_body(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('token-initial')]);
        $client = $this->mockedClient($mockClient, null);

        $token = $client->oauth->exchangeAuthorizationCode(
            code: 'authorization-code',
            redirectUri: 'https://fake-tpp.com/ponto-authorization',
            codeVerifier: 'code-verifier',
        );

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('POST', $pendingRequest->getMethod()->value);
        $this->assertSame('https://api.ibanity.com/ponto-connect/oauth2/token', $pendingRequest->getUrl());
        $this->assertSame(
            'Basic '.base64_encode(self::CLIENT_ID.':'.self::CLIENT_SECRET),
            $pendingRequest->headers()->get('Authorization'),
        );
        $this->assertSame('application/x-www-form-urlencoded', $pendingRequest->headers()->get('Content-Type'));
        $this->assertSame([
            'grant_type' => 'authorization_code',
            'code' => 'authorization-code',
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => 'https://fake-tpp.com/ponto-authorization',
            'code_verifier' => 'code-verifier',
        ], $pendingRequest->body()?->all());

        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame('lG4ImlCW8rsg9rKyPIybO3wY0QXV8qh-qqDVL65djrs.mf_abHC4LOGgfQInZBPpKb6RyRBSkdB5MGSbh-QPL8U', $token->accessToken);
        $this->assertSame('qEn4LR6UCk829VVqXoMf-vEQr6VoK6h0GusdgZRtDSc.YeT76p5iPj1gjsC6dZrUPa3qOfthSnQjwHaJHv3rOMc', $token->refreshToken);
        $this->assertSame(1800, $token->expiresIn);
        $this->assertSame(['offline_access', 'ai', 'pi', 'name'], $token->scopes());
        $this->assertTrue($token->hasScope('pi'));
    }

    public function test_token_requests_never_send_the_customer_bearer_token(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('token-refreshed')]);
        $client = $this->mockedClient($mockClient, 'customer-access-token');

        $client->oauth->refresh('old-refresh-token');

        $authorization = $this->lastRequest($mockClient)->headers()->get('Authorization');
        $this->assertStringStartsWith('Basic ', (string) $authorization);
    }

    public function test_refresh_returns_the_rotated_refresh_token(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('token-refreshed')]);
        $client = $this->mockedClient($mockClient, null);

        $token = $client->oauth->refresh('CIGtQIF2eA4XItkiPI_CXuEoEHrTVAEZeBq1QZLb7aY.LHfac5zNSJEsIJnOvhJz4dq1KsKblRhUXdgL9YV4c4I');

        $this->assertSame([
            'grant_type' => 'refresh_token',
            'refresh_token' => 'CIGtQIF2eA4XItkiPI_CXuEoEHrTVAEZeBq1QZLb7aY.LHfac5zNSJEsIJnOvhJz4dq1KsKblRhUXdgL9YV4c4I',
            'client_id' => self::CLIENT_ID,
        ], $this->lastRequest($mockClient)->body()?->all());
        $this->assertSame('XmAgO-ZPNq-HIU2nJrMqbyAuJfdHQzLN6_mme4ORuYE.9-5UR224yNsPUokOGmoRXf02L7IRq-5Nlba8d3_3JN8', $token->refreshToken);
        $this->assertSame(1799, $token->expiresIn);
    }

    public function test_refresh_with_a_used_refresh_token_throws_invalid_grant(): void
    {
        $mockClient = new MockClient([
            MockResponse::make($this->fixture('token-invalid-grant'), 400, ['ibanity-request-id' => '00070A610440C8610A4306EB01BB5C7D3A551A43510']),
        ]);
        $client = $this->mockedClient($mockClient, null);

        try {
            $client->oauth->refresh('already-used-refresh-token');
            $this->fail('Expected an InvalidGrantException.');
        } catch (InvalidGrantException $exception) {
            $this->assertSame(400, $exception->status);
            $this->assertSame(['invalid_grant'], $exception->errorCodes());
            $this->assertSame('00070A610440C8610A4306EB01BB5C7D3A551A43510', $exception->requestId);
            $this->assertStringNotContainsString('already-used-refresh-token', $exception->getMessage());
        }
    }

    public function test_client_credentials_returns_a_token_without_refresh_token(): void
    {
        $mockClient = new MockClient([$this->fixtureResponse('token-client')]);
        $client = $this->mockedClient($mockClient, null);

        $token = $client->oauth->clientCredentials();

        $this->assertSame(['grant_type' => 'client_credentials'], $this->lastRequest($mockClient)->body()?->all());
        $this->assertNull($token->refreshToken);
        $this->assertSame([], $token->scopes());
    }

    public function test_revoke_posts_the_refresh_token_to_the_revoke_endpoint(): void
    {
        $mockClient = new MockClient([MockResponse::make([], 200)]);
        $client = $this->mockedClient($mockClient, null);

        $client->oauth->revoke('refresh-token-to-revoke');

        $pendingRequest = $this->lastRequest($mockClient);
        $this->assertSame('https://api.ibanity.com/ponto-connect/oauth2/revoke', $pendingRequest->getUrl());
        $this->assertSame(['token' => 'refresh-token-to-revoke'], $pendingRequest->body()?->all());
        $this->assertStringStartsWith('Basic ', (string) $pendingRequest->headers()->get('Authorization'));
    }

    public function test_token_debug_output_hides_the_tokens(): void
    {
        $token = Token::fromArray($this->fixture('token-initial'));

        $debugOutput = print_r($token, true);

        $this->assertStringNotContainsString($token->accessToken, $debugOutput);
        $this->assertStringNotContainsString((string) $token->refreshToken, $debugOutput);
    }
}
