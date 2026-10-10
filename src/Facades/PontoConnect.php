<?php

namespace PontoConnect\Facades;

use Illuminate\Support\Facades\Facade;
use PontoConnect\Api\PontoConnectApiClient;
use PontoConnect\Configuration;
use PontoConnect\Enums\Language;
use PontoConnect\Enums\Scope;
use PontoConnect\PontoConnectManager;

/**
 * @method static PontoConnectApiClient api(?string $accessToken = null)
 * @method static PontoConnectApiClient withAccessToken(string $accessToken)
 * @method static string authorizationUrl(string $redirectUri, string $state, string $codeChallenge, list<string|Scope>|null $scopes = null, Language|string|null $language = null, ?string $onboardingDetailsId = null)
 * @method static Configuration configuration()
 *
 * @see PontoConnectManager
 */
class PontoConnect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PontoConnectManager::class;
    }
}
