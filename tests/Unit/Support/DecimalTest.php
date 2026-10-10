<?php

namespace PontoConnect\Tests\Unit\Support;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PontoConnect\Support\Decimal;

class DecimalTest extends TestCase
{
    /**
     * @return array<string, array{0: int|float|string|null, 1: string|null}>
     */
    public static function amounts(): array
    {
        return [
            'negative integer' => [-85, '-85.00'],
            'zero' => [0, '0.00'],
            'two decimals' => [84.42, '84.42'],
            'negative two decimals' => [-99.29, '-99.29'],
            'one decimal is padded' => [1.5, '1.50'],
            'more decimals are kept' => [0.125, '0.125'],
            'float addition artefact is not rounded away' => [0.1 + 0.2, '0.30000000000000004'],
            'large amount without exponent' => [12345678.9, '12345678.90'],
            'tiny amount without exponent' => [0.00001, '0.00001'],
            'numeric string' => ['12.3', '12.30'],
            'null' => [null, null],
        ];
    }

    #[DataProvider('amounts')]
    public function test_converts_json_numbers_to_decimal_strings(int|float|string|null $number, ?string $expected): void
    {
        $this->assertSame($expected, Decimal::fromNumber($number));
    }

    public function test_json_decoded_amounts_keep_their_printed_value(): void
    {
        $decoded = json_decode('{"amount": -465.33, "fee": 0.07}', true);

        $this->assertSame('-465.33', Decimal::fromNumber($decoded['amount']));
        $this->assertSame('0.07', Decimal::fromNumber($decoded['fee']));
    }

    public function test_minimum_scale_can_be_disabled(): void
    {
        $this->assertSame('3', Decimal::fromNumber(3, minimumScale: 0));
        $this->assertSame('1.48', Decimal::fromNumber(1.48, minimumScale: 0));
    }

    public function test_non_numeric_string_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::fromNumber('twelve');
    }
}
