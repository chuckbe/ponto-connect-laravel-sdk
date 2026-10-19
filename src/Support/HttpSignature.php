<?php

namespace PontoConnect\Support;

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use PontoConnect\Configuration;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use SensitiveParameter;

/**
 * Ibanity's HTTP signature for live requests, next to mutual TLS: a
 * `Digest` header (SHA-512 of the body) and a `Signature` header (hs2019,
 * RSA-PSS with SHA-256) over the request target, the host, the digest,
 * the creation time, the Authorization header and every Ibanity header, as
 * Ibanity's own SDKs sign them.
 */
final class HttpSignature
{
    public const ALGORITHM = 'hs2019';

    private const HASH = 'sha256';

    private const SALT_LENGTH = 32;

    private PrivateKey $privateKey;

    public function __construct(
        private readonly string $certificateId,
        #[SensitiveParameter]
        string $privateKey,
        #[SensitiveParameter]
        ?string $passphrase = null,
    ) {
        $key = RSA::loadPrivateKey($privateKey, $passphrase ?? '');
        if (! $key instanceof PrivateKey) {
            throw new RuntimeException('The Ponto Connect signature key is not an RSA private key.');
        }

        $this->privateKey = $key
            ->withPadding(RSA::SIGNATURE_PSS)
            ->withHash(self::HASH)
            ->withMGFHash(self::HASH)
            ->withSaltLength(self::SALT_LENGTH);
    }

    public static function fromConfiguration(Configuration $configuration): self
    {
        $privateKeyPath = (string) $configuration->signaturePrivateKeyPath;
        $privateKey = is_readable($privateKeyPath) ? file_get_contents($privateKeyPath) : false;
        if ($privateKey === false) {
            throw new RuntimeException("The Ponto Connect signature key at [{$privateKeyPath}] can't be read.");
        }

        return new self((string) $configuration->signatureCertificateId, $privateKey, $configuration->signaturePrivateKeyPassphrase);
    }

    /**
     * The request with its `Digest` and `Signature` headers.
     */
    public function sign(RequestInterface $request, ?int $createdAt = null): RequestInterface
    {
        $body = (string) $request->getBody();
        $request->getBody()->rewind();

        $request = $request->withHeader('Digest', $this->digest($body));
        $createdAt ??= time();

        $signedHeaders = $this->headersToSign($request);
        $signature = $this->privateKey->sign($this->signingString($request, $signedHeaders, $createdAt));

        return $request->withHeader('Signature', implode(',', [
            sprintf('keyId="%s"', $this->certificateId),
            sprintf('created="%d"', $createdAt),
            sprintf('algorithm="%s"', self::ALGORITHM),
            sprintf('headers="%s"', implode(' ', $signedHeaders)),
            sprintf('signature="%s"', self::base64UrlSafe($signature)),
        ]));
    }

    public function digest(string $body): string
    {
        return 'SHA-512='.self::base64UrlSafe(hash('sha512', $body, true));
    }

    /**
     * The request target, host, digest and creation time, then the
     * Authorization header and the Ibanity headers the request carries.
     *
     * @return list<string>
     */
    private function headersToSign(RequestInterface $request): array
    {
        $headers = ['(request-target)', 'host', 'digest', '(created)'];

        if ($request->hasHeader('Authorization')) {
            $headers[] = 'authorization';
        }

        foreach (array_keys($request->getHeaders()) as $headerName) {
            if (stripos((string) $headerName, 'ibanity') !== false) {
                $headers[] = strtolower((string) $headerName);
            }
        }

        return $headers;
    }

    /**
     * @param list<string> $signedHeaders
     */
    private function signingString(RequestInterface $request, array $signedHeaders, int $createdAt): string
    {
        $lines = [];
        foreach ($signedHeaders as $signedHeader) {
            $lines[] = $signedHeader.': '.$this->headerValue($request, $signedHeader, $createdAt);
        }

        return implode("\n", $lines);
    }

    private function headerValue(RequestInterface $request, string $signedHeader, int $createdAt): string
    {
        return match ($signedHeader) {
            '(request-target)' => $this->requestTarget($request),
            'host' => $request->getUri()->getHost(),
            '(created)' => (string) $createdAt,
            default => $request->getHeaderLine($signedHeader),
        };
    }

    private function requestTarget(RequestInterface $request): string
    {
        $uri = $request->getUri();
        $target = $uri->getPath();
        if ($uri->getQuery() !== '') {
            $target .= '?'.$uri->getQuery();
        }

        return strtolower($request->getMethod()).' '.$target;
    }

    private static function base64UrlSafe(string $bytes): string
    {
        return strtr(base64_encode($bytes), '+/', '-_');
    }
}
