<?php

namespace PontoConnect\Api\Requests\UserInfo;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get User Info: GET /ponto-connect/userinfo.
 */
class GetUserInfoRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/ponto-connect/userinfo';
    }
}
