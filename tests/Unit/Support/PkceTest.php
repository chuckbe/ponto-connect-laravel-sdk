<?php

namespace PontoConnect\Tests\Unit\Support;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PontoConnect\Support\Pkce;

class PkceTest extends TestCase
{
    public function test_challenge_matches_the_rfc_7636_example(): void
    {
        $this->assertSame(
            'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            Pkce::challenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'),
        );
    }

    public function test_generated_verifier_uses_only_unreserved_characters(): void
    {
        $verifier = Pkce::generateVerifier();

        $this->assertSame(64, strlen($verifier));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-._~]+$/', $verifier);
        $this->assertNotSame($verifier, Pkce::generateVerifier());
    }

    public function test_generate_returns_a_matching_verifier_and_challenge(): void
    {
        $pkce = Pkce::generate(128);

        $this->assertSame(128, strlen($pkce['verifier']));
        $this->assertSame(Pkce::challenge($pkce['verifier']), $pkce['challenge']);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]{43}$/', $pkce['challenge']);
    }

    public function test_verifier_length_must_be_between_43_and_128(): void
    {
        $this->assertSame(43, strlen(Pkce::generateVerifier(43)));

        $this->expectException(InvalidArgumentException::class);

        Pkce::generateVerifier(42);
    }

    public function test_verifier_longer_than_128_characters_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Pkce::generateVerifier(129);
    }
}
