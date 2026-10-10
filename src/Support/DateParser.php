<?php

namespace PontoConnect\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

final class DateParser
{
    /**
     * Parse an ISO 8601 timestamp from the API into an immutable UTC date.
     */
    public static function parse(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Expected an ISO 8601 date-time string.');
        }

        try {
            $date = new DateTimeImmutable($value);
        } catch (Exception $exception) {
            throw new InvalidArgumentException(sprintf('Invalid date-time "%s".', $value), 0, $exception);
        }

        return $date->setTimezone(new DateTimeZone('UTC'));
    }

    public static function format(?DateTimeImmutable $date): ?string
    {
        return $date?->format('Y-m-d\TH:i:s.up');
    }
}
