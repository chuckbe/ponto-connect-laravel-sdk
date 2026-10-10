<?php

namespace PontoConnect\Api\Resources;

use PontoConnect\Api\Requests\Webhook\GetWebhookKeysRequest;

/**
 * Webhooks API reference, "Key".
 */
class WebhookResource extends Resource
{
    /**
     * The JSON Web Key Set used to sign webhooks. Ponto allows caching it
     * for up to an hour; refetch it when a webhook uses an unknown key id.
     *
     * @return array{keys: list<array<string, string>>}
     */
    public function keys(): array
    {
        $body = $this->sendAndDecode(new GetWebhookKeysRequest());

        $keys = [];
        foreach ($body['keys'] ?? [] as $key) {
            if (is_array($key)) {
                $keys[] = $key;
            }
        }

        return ['keys' => $keys];
    }
}
