<?php

namespace PontoConnect\Api\Requests\OAuth;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use SensitiveParameter;

/**
 * Base class for the OAuth2 endpoints: form-encoded bodies authenticated
 * with HTTP Basic auth using the application's client id and secret.
 */
abstract class OAuthRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $clientId,
        #[SensitiveParameter]
        protected string $clientSecret,
    ) {
    }

    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator($this->clientId, $this->clientSecret);
    }
}
