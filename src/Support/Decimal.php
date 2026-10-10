<?php

namespace PontoConnect\Support;

use InvalidArgumentException;

/**
 * Converts the JSON numbers Ponto uses for amounts and balances into
 * decimal strings, so callers never do money arithmetic on floats.
 *
 * A float is written with the fewest decimals that convert back to exactly
 * the same float (-99.29 becomes "-99.29", never "-99.2899999999999920").
 * The result is then padded, never rounded, to a minimum scale, which is
 * 2 by default: -85 becomes "-85.00" and 0.125 stays "0.125".
 */
final class Decimal
{
    private const MAXIMUM_SCALE = 17;

    public static function fromNumber(int|float|string|null $number, int $minimumScale = 2): ?string
    {
        if ($number === null) {
            return null;
        }

        if (is_string($number)) {
            if (! is_numeric($number)) {
                throw new InvalidArgumentException(sprintf('"%s" is not a numeric value.', $number));
            }

            return self::padScale(trim($number), $minimumScale);
        }

        if (is_int($number)) {
            return self::padScale((string) $number, $minimumScale);
        }

        return self::padScale(self::shortestRoundTrip($number), $minimumScale);
    }

    private static function shortestRoundTrip(float $number): string
    {
        if (! is_finite($number)) {
            throw new InvalidArgumentException('Amounts must be finite numbers.');
        }

        for ($scale = 0; $scale <= self::MAXIMUM_SCALE; $scale++) {
            $candidate = sprintf('%.'.$scale.'F', $number);
            if ((float) $candidate === $number) {
                return $candidate;
            }
        }

        return sprintf('%.'.self::MAXIMUM_SCALE.'F', $number);
    }

    private static function padScale(string $decimal, int $minimumScale): string
    {
        if ($minimumScale <= 0) {
            return $decimal;
        }

        $separatorPosition = strpos($decimal, '.');
        if ($separatorPosition === false) {
            return $decimal.'.'.str_repeat('0', $minimumScale);
        }

        $currentScale = strlen($decimal) - $separatorPosition - 1;
        if ($currentScale >= $minimumScale) {
            return $decimal;
        }

        return $decimal.str_repeat('0', $minimumScale - $currentScale);
    }
}
