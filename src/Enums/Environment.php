<?php

namespace PontoConnect\Enums;

enum Environment: string
{
    case SANDBOX = 'sandbox';
    case LIVE = 'live';

    public function authorizationUrl(): string
    {
        if ($this === self::LIVE) {
            return 'https://authorization.myponto.com';
        }

        return 'https://sandbox-authorization.myponto.com';
    }
}
