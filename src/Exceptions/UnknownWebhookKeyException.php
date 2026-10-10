<?php

namespace PontoConnect\Exceptions;

/**
 * The webhook was signed with a key id (`kid`) that is not in the given key set.
 *
 * Ponto rotates its signing keys, so refetch the keys from the webhooks API
 * (bypassing your cache) and verify once more before rejecting the request.
 */
class UnknownWebhookKeyException extends WebhookVerificationException
{
    public function __construct(public readonly string $keyId)
    {
        parent::__construct(sprintf('Webhook signed with unknown key id "%s".', $keyId));
    }
}
