<?php

namespace PontoConnect\Api\Requests\Webhook;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List keys (Webhooks API): GET /webhooks/keys.
 */
class GetWebhookKeysRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/webhooks/keys';
    }
}
