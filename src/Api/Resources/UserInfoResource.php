<?php

namespace PontoConnect\Api\Resources;

use PontoConnect\Api\Requests\UserInfo\GetUserInfoRequest;
use PontoConnect\Data\UserInfo;

/**
 * API reference, "User Info". Requires a customer access token.
 */
class UserInfoResource extends Resource
{
    public function get(): UserInfo
    {
        return UserInfo::fromArray($this->sendAndDecode(new GetUserInfoRequest()));
    }
}
