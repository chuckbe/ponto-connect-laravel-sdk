<?php

namespace PontoConnect\Api\Requests\Concerns;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Prepares sandbox transaction attributes for the API: decimal-string
 * amounts become JSON numbers, dates become ISO 8601 UTC strings and
 * null values are left out.
 */
trait NormalizesSandboxTransactionAttributes
{
    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    protected function normalizeTransactionAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (in_array($name, ['amount', 'fee'], true)) {
                $value = $this->toJsonNumber($name, $value);
            }

            if ($value instanceof DateTimeInterface) {
                $value = DateTimeImmutable::createFromInterface($value)
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->format('Y-m-d\TH:i:s\Z');
            }

            $normalized[$name] = $value;
        }

        return $normalized;
    }

    private function toJsonNumber(string $name, mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (! is_string($value) || ! is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('The "%s" attribute must be numeric.', $name));
        }

        if (str_contains($value, '.')) {
            return (float) $value;
        }

        return (int) $value;
    }
}
