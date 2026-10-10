<?php

namespace PontoConnect\Exceptions;

use JsonException;
use RuntimeException;
use Saloon\Http\Response;
use Throwable;

/**
 * Thrown for every failed (4xx/5xx) Ponto Connect API response.
 *
 * Carries the HTTP status, the error objects from the body and the
 * `ibanity-request-id` header to quote when contacting Ponto support.
 */
class PontoConnectException extends RuntimeException
{
    /**
     * @param list<array{code: string|null, detail: string|null, meta: array<string, mixed>}> $errors
     */
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly array $errors = [],
        public readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public static function fromResponse(Response $response, ?Throwable $previous = null): self
    {
        $status = $response->status();
        $body = self::decodeBody($response);
        $errors = self::extractErrors($body);
        $requestId = $response->header('ibanity-request-id');
        if (! is_string($requestId) || $requestId === '') {
            $requestId = null;
        }

        $message = self::buildMessage($status, $errors, $requestId);

        if (self::containsErrorCode($errors, 'invalid_grant')) {
            return new InvalidGrantException($message, $status, $errors, $requestId, $previous);
        }

        return new self($message, $status, $errors, $requestId, $previous);
    }

    /**
     * @return list<string>
     */
    public function errorCodes(): array
    {
        $codes = [];
        foreach ($this->errors as $error) {
            if ($error['code'] !== null) {
                $codes[] = $error['code'];
            }
        }

        return $codes;
    }

    public function hasErrorCode(string $code): bool
    {
        return in_array($code, $this->errorCodes(), true);
    }

    /**
     * @return array<mixed>
     */
    private static function decodeBody(Response $response): array
    {
        try {
            return $response->json();
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * Supports both the JSON:API error format (`{"errors": [{"code", "detail"}]}`)
     * and the OAuth2 format used by the token endpoints (`{"error", "error_description"}`).
     *
     * @param array<mixed> $body
     *
     * @return list<array{code: string|null, detail: string|null, meta: array<string, mixed>}>
     */
    private static function extractErrors(array $body): array
    {
        $errors = [];

        if (isset($body['errors']) && is_array($body['errors'])) {
            foreach ($body['errors'] as $error) {
                if (! is_array($error)) {
                    continue;
                }

                $meta = [];
                if (isset($error['meta']) && is_array($error['meta'])) {
                    $meta = $error['meta'];
                }

                $errors[] = [
                    'code' => isset($error['code']) ? (string) $error['code'] : null,
                    'detail' => isset($error['detail']) ? (string) $error['detail'] : null,
                    'meta' => $meta,
                ];
            }
        }

        if (isset($body['error']) && is_string($body['error'])) {
            $errors[] = [
                'code' => $body['error'],
                'detail' => isset($body['error_description']) ? (string) $body['error_description'] : null,
                'meta' => [],
            ];
        }

        return $errors;
    }

    /**
     * @param list<array{code: string|null, detail: string|null, meta: array<string, mixed>}> $errors
     */
    private static function containsErrorCode(array $errors, string $code): bool
    {
        foreach ($errors as $error) {
            if ($error['code'] === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{code: string|null, detail: string|null, meta: array<string, mixed>}> $errors
     */
    private static function buildMessage(int $status, array $errors, ?string $requestId): string
    {
        $message = sprintf('Ponto Connect request failed with status %d', $status);

        $descriptions = [];
        foreach ($errors as $error) {
            $descriptions[] = trim(sprintf('%s %s', $error['code'] ?? '', $error['detail'] ?? ''));
        }

        $descriptions = array_filter($descriptions);
        if ($descriptions !== []) {
            $message .= ': '.implode('; ', $descriptions);
        }

        if ($requestId !== null) {
            $message .= sprintf(' (ibanity-request-id: %s)', $requestId);
        }

        return $message;
    }
}
