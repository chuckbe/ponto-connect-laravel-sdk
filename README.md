# Ponto Connect Laravel SDK

A Laravel SDK for the [Ponto Connect API v2](https://documentation.ibanity.com/ponto-connect/2/api) by Isabel Group (Ibanity). Built on [Saloon PHP](https://docs.saloon.dev/) with a fluent, resource-based interface: OAuth with PKCE, accounts, transactions, synchronizations, reauthorization, organization endpoints, sandbox test data and webhook signature verification.

Payment initiation endpoints (payments, bulk payments, payment requests and their activation requests) are not implemented yet.

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Saloon 4 (`saloonphp/saloon` ^4.0)
- A Ponto Connect application in the [Isabel API Hub developer portal](https://documentation.ibanity.com/getting-started), with its TLS certificate

## Installation

```bash
composer require chuckbe/ponto-connect-laravel-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ponto-connect-config
```

## Configuration

```env
PONTO_CONNECT_ENVIRONMENT=sandbox
PONTO_CONNECT_CLIENT_ID=your-client-id
PONTO_CONNECT_CLIENT_SECRET=your-client-secret
PONTO_CONNECT_APPLICATION_ID=your-application-id
PONTO_CONNECT_CERTIFICATE_PATH=/home/forge/.ponto/certificate.pem
PONTO_CONNECT_PRIVATE_KEY_PATH=/home/forge/.ponto/private_key.pem
PONTO_CONNECT_PRIVATE_KEY_PASSPHRASE=your-key-passphrase
PONTO_CONNECT_CA_CHAIN_PATH=
PONTO_CONNECT_SIGNATURE_CERTIFICATE_ID=
PONTO_CONNECT_SIGNATURE_PRIVATE_KEY_PATH=
PONTO_CONNECT_SIGNATURE_PRIVATE_KEY_PASSPHRASE=
PONTO_CONNECT_API_URL=https://api.ibanity.com
PONTO_CONNECT_SCOPES="ai pi name offline_access"
PONTO_CONNECT_TIMEOUT=30
```

| Variable | Description |
|---|---|
| `PONTO_CONNECT_ENVIRONMENT` | `sandbox` or `live`. Selects the authorization host (`https://sandbox-authorization.myponto.com` or `https://authorization.myponto.com`). The API host is the same for both; Ponto routes your calls by certificate. |
| `PONTO_CONNECT_CLIENT_ID` / `_CLIENT_SECRET` | OAuth client credentials from the security tab of the Ponto Connect product. |
| `PONTO_CONNECT_APPLICATION_ID` | The application id, used as the expected `aud` of webhook signatures. Defaults to the client id. |
| `PONTO_CONNECT_CERTIFICATE_PATH` / `_PRIVATE_KEY_PATH` / `_PRIVATE_KEY_PASSPHRASE` | The TLS certificate and private key for mutual TLS. Every API request uses them. |
| `PONTO_CONNECT_CA_CHAIN_PATH` | Optional CA chain to verify the API server. Uses the system CA bundle when empty. |
| `PONTO_CONNECT_SIGNATURE_CERTIFICATE_ID` / `_SIGNATURE_PRIVATE_KEY_PATH` / `_SIGNATURE_PRIVATE_KEY_PASSPHRASE` | The signature certificate for live applications: its ID (shown in the developer portal) and its private key. When both the ID and the key are set, every request is signed (HTTP signature). Leave them empty in the sandbox. |
| `PONTO_CONNECT_SCOPES` | Scopes requested in the authorization URL, separated by spaces or commas. Default `ai pi name offline_access`. Drop `pi` if your application may not initiate payments. |

### Certificates

Keep the certificate and private key **outside the repository** and outside `public/`: for example `~/.config/chuck/ponto-sandbox/` locally, and a directory owned by the deploy user on the server, provisioned by your server recipes. Never commit them. Restrict the private key's permissions (`chmod 600`).

The sandbox and live environments use different certificates and client credentials. A live application also has a **signature certificate** next to the TLS certificate: create it in the developer portal and set the `PONTO_CONNECT_SIGNATURE_*` values. "Going live" means swapping and adding these values, not changing code.

## Usage

```php
use PontoConnect\Facades\PontoConnect;

PontoConnect::api();                          // no token: public endpoints and the OAuth token endpoints
PontoConnect::withAccessToken($accessToken);  // customer (or client) access token
```

The manager never stores a token. Each call returns a fresh connector, so tokens for different organizations cannot leak into each other, for example in queue workers.

### 1. Connect with PKCE

```php
use PontoConnect\Facades\PontoConnect;
use PontoConnect\Support\Pkce;

$pkce = Pkce::generate();                     // ['verifier' => ..., 'challenge' => ...]
$state = Str::random(40);

session(['ponto.state' => $state, 'ponto.verifier' => $pkce['verifier']]);

return redirect()->away(PontoConnect::authorizationUrl(
    redirectUri: route('integrations.ponto.callback'),
    state: $state,
    codeChallenge: $pkce['challenge'],
    language: 'nl',                           // optional: en, fr or nl
    // scopes: ['ai', 'name', 'offline_access'], // optional override of the configured scopes
    // onboardingDetailsId: $onboardingDetails->id, // optional, see below
));
```

Optionally, prefill the Ponto sign-up first with a client access token. Use the id within five minutes:

```php
$clientToken = PontoConnect::api()->oauth->clientCredentials();

$onboardingDetails = PontoConnect::withAccessToken($clientToken->accessToken)
    ->onboardingDetails
    ->create([
        'email' => $user->email,
        'firstName' => $user->first_name,
        'lastName' => $user->last_name,
        'organizationName' => $company->name,
        'vatNumber' => $company->vat_number,
        'addressCountry' => 'BE',
    ]);
```

### 2. Exchange the code

```php
abort_unless(hash_equals(session('ponto.state'), $request->query('state')), 403);

$token = PontoConnect::api()->oauth->exchangeAuthorizationCode(
    code: $request->query('code'),
    redirectUri: route('integrations.ponto.callback'),
    codeVerifier: session()->pull('ponto.verifier'),
);

$userInfo = PontoConnect::withAccessToken($token->accessToken)->userInfo->get();

$connection->update([
    'organization_id' => $userInfo->sub,      // store it: needed for usage and integration deletion
    'refresh_token' => $token->refreshToken,  // encrypted cast
    'access_token' => $token->accessToken,
    'access_token_expires_at' => $token->expiresAt,
]);
```

`$userInfo` also exposes `name`, `onboardingComplete`, `paymentsActivated`, `paymentsActivationRequested`, `paymentRequestsActivated`, `paymentRequestsActivationRequested`, and, when the customer acts as a representative, `representativeOrganizationId` and `representativeOrganizationName`.

### 3. Refresh with rotation

Access tokens live 30 minutes. **Refresh tokens are single-use**: every refresh returns a new refresh token and invalidates the old one immediately.

```php
use Illuminate\Support\Facades\Cache;
use PontoConnect\Exceptions\InvalidGrantException;

$accessToken = Cache::lock("ponto-refresh:{$connection->id}", 30)->block(10, function () use ($connection) {
    $connection->refresh();

    if ($connection->access_token_expires_at->isFuture()) {
        return $connection->access_token;     // another process refreshed in the meantime
    }

    try {
        $token = PontoConnect::api()->oauth->refresh($connection->refresh_token);
    } catch (InvalidGrantException $exception) {
        $connection->markRevoked();           // used, expired (90 days) or revoked by the customer
        throw $exception;
    }

    $connection->update([                     // persist immediately, outside any DB transaction
        'refresh_token' => $token->refreshToken,
        'access_token' => $token->accessToken,
        'access_token_expires_at' => $token->expiresAt,
    ]);

    return $token->accessToken;
});
```

- **Lock around the refresh.** Two workers refreshing with the same token means one gets `invalid_grant` and the integration looks revoked.
- **Persist the new refresh token straight away, outside a database transaction.** If an outer transaction rolls back after a successful refresh, the stored token is already invalid and the integration is lost.
- `InvalidGrantException` (a `PontoConnectException`) is thrown only for `invalid_grant`. The customer then has to connect again.
- Refresh at least once every 90 days, or the refresh token expires.

### 4. Accounts and transactions (cursor pagination)

Lists return a `Page` with `items`, `nextCursor` (`page[after]`), `previousCursor` (`page[before]`) and `limit`. Pages accept `?int $limit = null` (1 to 100, default 10), `?string $after` and `?string $before`.

```php
$ponto = PontoConnect::withAccessToken($accessToken);

$page = $ponto->account->list(limit: 50);
foreach ($page as $account) {
    echo $account->reference;                 // IBAN
    echo $account->currentBalance;            // "1234.50" (string)
    echo $account->latestSynchronization?->status;
}
$nextPage = $ponto->account->list(limit: 50, after: $page->nextCursor);

// Or walk every page lazily (one request per page, 100 items per page)
foreach ($ponto->transaction->all($accountId) as $transaction) {
    $transaction->amount;                     // "-85.00"
    $transaction->remittanceInformation;      // "+++090/9337/55493+++"
    $transaction->remittanceInformationType;  // "structured"
    $transaction->valueDate;                  // DateTimeImmutable (UTC)
}

$ponto->pendingTransaction->list($accountId);   // replace your stored pending transactions each time
$ponto->account->revoke($accountId);            // remove an account from the integration
```

**Amounts are strings.** Ponto sends amounts and balances as JSON numbers. They are converted to decimal strings with the shortest representation that matches the decoded number exactly, padded (never rounded) to at least two decimals: `-85` becomes `"-85.00"`, `-99.29` stays `"-99.29"`. Use `bcmath` or a money library, never floats. Dates are `DateTimeImmutable` in UTC.

### 5. Synchronizations (customer present only)

Ponto synchronizes in the background four times a day. You may trigger an extra synchronization **only while the customer is actively using your application**, and you must pass their real IP address. A new synchronization of the same account and subtype is refused within 30 minutes of the previous one.

```php
use PontoConnect\Enums\SynchronizationSubtype;

$synchronization = $ponto->synchronization->create(
    resourceId: $accountId,
    subtype: SynchronizationSubtype::ACCOUNT_TRANSACTIONS,
    customerIpAddress: $request->ip(),
    idempotencyKey: (string) Str::uuid(),     // optional, any POST/PATCH accepts one
);

$synchronization = $ponto->synchronization->get($synchronization->id);
if ($synchronization->isSuccessful()) {
    foreach ($ponto->transaction->allUpdatedForSynchronization($synchronization->id) as $transaction) {
        // new or changed transactions
    }
}
if ($synchronization->hasFailed()) {
    $synchronization->errorCodes();           // e.g. ['authorizationExpired']
}
```

### 6. Reauthorization

When an account's bank consent expires (`authorizationExpirationExpectedAt`, or a synchronization error like `authorizationExpired`):

```php
$reauthorization = $ponto->reauthorizationRequest->create(
    accountId: $accountId,
    redirectUri: route('integrations.ponto.reauthorized'),
);

return redirect()->away($reauthorization->redirectUrl);
```

### 7. Webhooks

```php
use Illuminate\Support\Facades\Cache;
use PontoConnect\Enums\WebhookEventType;
use PontoConnect\Exceptions\UnknownWebhookKeyException;
use PontoConnect\Exceptions\WebhookVerificationException;

public function __invoke(Request $request)
{
    $verifier = PontoConnect::webhookVerifier();
    $signature = (string) $request->header('Signature');
    $rawBody = $request->getContent();        // the raw body, not the parsed JSON

    try {
        try {
            $keys = Cache::remember('ponto-connect.webhook-keys', 3600, fn () => PontoConnect::api()->webhook->keys());
            $event = $verifier->verify($signature, $rawBody, $keys);
        } catch (UnknownWebhookKeyException) {
            $keys = PontoConnect::api()->webhook->keys();  // Ponto rotated its signing keys
            Cache::put('ponto-connect.webhook-keys', $keys, 3600);
            $event = $verifier->verify($signature, $rawBody, $keys);
        }
    } catch (WebhookVerificationException) {
        abort(400);
    }

    if (! Cache::add("ponto-connect.webhook-jti.{$event->jti}", true, now()->addDay())) {
        return response()->noContent();       // replayed
    }

    if ($event->is(WebhookEventType::ACCOUNT_TRANSACTIONS_CREATED)) {
        SyncPontoTransactions::dispatch($event->organizationId, $event->accountId, $event->synchronizationId);
    }

    return response()->noContent();           // answer within 5 seconds, do the work in a job
}
```

The verifier accepts only RS512, picks the key by `kid`, checks `aud` (your application id), `iss` (`https://api.ibanity.com`) and `digest` (base64 SHA-512 of the raw body), and checks `exp`/`iat` with 30 seconds of leeway. `WebhookEvent` exposes `id`, `type`, `eventType()`, `createdAt`, `attributes`, `count()`, `synchronizationSubtype()`, `accountId`, `organizationId`, `synchronizationId`, `paymentRequestId` and `jti`.

### 8. Sandbox test transactions

Sandbox only, with a customer access token of a sandbox integration:

```php
$fakeAccounts = $ponto->sandbox->listFinancialInstitutionAccounts($financialInstitutionId);

$ponto->sandbox->createFinancialInstitutionTransaction(
    financialInstitutionId: $financialInstitutionId,
    financialInstitutionAccountId: $fakeAccounts->items[0]->id,
    attributes: [
        'valueDate' => now(),
        'executionDate' => now(),
        'amount' => '121.00',
        'currency' => 'EUR',
        'description' => 'Payment invoice 2026-0042',
        'remittanceInformationType' => 'structured',
        'remittanceInformation' => '+++090/9337/55493+++',
        'counterpartName' => 'Acme BV',
        'counterpartReference' => 'BE68539007547034',
    ],
);
```

The transaction appears in `transaction->list()` after the next synchronization of that account.

### Organization endpoints (client access token)

```php
$client = PontoConnect::withAccessToken(PontoConnect::api()->oauth->clientCredentials()->accessToken);

$client->usage->get($organizationId, '2026-09');          // accountCount is a decimal string (prorated)
$client->integrationAccount->all();                       // every account linked to your application
$client->integration->delete($organizationId);            // revoke, even without a valid refresh token
```

`PontoConnect::api()->oauth->revoke($refreshToken)` revokes with the refresh token instead.

## Errors

Every 4xx/5xx response throws `PontoConnect\Exceptions\PontoConnectException`:

```php
try {
    $ponto->account->get($accountId);
} catch (PontoConnectException $exception) {
    $exception->status;        // 404
    $exception->errorCodes();  // the `code` of each error in the response
    $exception->errors;        // [['code' => ..., 'detail' => ..., 'meta' => [...]]]
    $exception->requestId;     // ibanity-request-id header, quote it to Ponto support
}
```

`InvalidGrantException` extends it for `invalid_grant` from the token endpoint.

## Security

- **Mutual TLS** on every request. Keep the certificate and key outside the repository, readable only by the app user.
- **Secrets**: the client secret, passphrase, access and refresh tokens are marked `#[SensitiveParameter]`. `Configuration` and `Token` hide them from `print_r`/`var_dump`. Do not log `Token::toArray()`. Store refresh tokens encrypted.
- **Webhooks**: always verify the signature against the raw body before using the payload. Store the `jti` to reject replays. Keep the event handler idempotent, because Ponto retries and does not guarantee order.
- **Synchronizations**: never send a fake or server IP address. Creating synchronizations without the customer present breaks the Ponto Connect terms (PSD2 RTS).
- **HTTP signatures**: live applications sign every request with the signature certificate, next to mutual TLS. Once the signature certificate is configured, the connector adds a `Digest` header (SHA-512 of the body) and a `Signature` header (`hs2019`, RSA-PSS with SHA-256). The signature covers the request target, host, digest, creation time, `Authorization` and every `Ibanity-*` header, as Ibanity's own SDKs do (`PontoConnect\Support\HttpSignature`). The sandbox sends requests unsigned.

## API reference

| Resource | Method | Endpoint |
|---|---|---|
| `oauth` | `authorizationUrl(redirectUri, state, codeChallenge, ?scopes, ?language, ?onboardingDetailsId)` | `{authorization host}/oauth2/auth` (no API call) |
| `oauth` | `exchangeAuthorizationCode(code, redirectUri, codeVerifier)` | `POST /ponto-connect/oauth2/token` |
| `oauth` | `refresh(refreshToken)` | `POST /ponto-connect/oauth2/token` |
| `oauth` | `clientCredentials()` | `POST /ponto-connect/oauth2/token` |
| `oauth` | `revoke(refreshToken)` | `POST /ponto-connect/oauth2/revoke` |
| `userInfo` | `get()` | `GET /ponto-connect/userinfo` |
| `onboardingDetails` | `create(attributes, ?idempotencyKey)` | `POST /ponto-connect/onboarding-details` |
| `financialInstitution` | `list(?limit, ?after, ?before, filters)`, `all(filters)`, `get(id)` | `GET /ponto-connect/financial-institutions[/{id}]` |
| `account` | `list(...)`, `all()`, `get(id)`, `revoke(id)` | `GET/DELETE /ponto-connect/accounts[/{id}]` |
| `transaction` | `list(accountId, ...)`, `all(accountId)`, `get(accountId, id)` | `GET /ponto-connect/accounts/{accountId}/transactions[/{id}]` |
| `transaction` | `listUpdatedForSynchronization(synchronizationId, ...)`, `allUpdatedForSynchronization(...)` | `GET /ponto-connect/synchronizations/{id}/updated-transactions` |
| `pendingTransaction` | `list`, `all`, `get`, `listUpdatedForSynchronization`, `allUpdatedForSynchronization` | `.../pending-transactions`, `.../updated-pending-transactions` |
| `synchronization` | `create(resourceId, subtype, customerIpAddress, resourceType = 'account', ?idempotencyKey)`, `get(id)` | `POST/GET /ponto-connect/synchronizations[/{id}]` |
| `reauthorizationRequest` | `create(accountId, redirectUri, ?idempotencyKey)` | `POST /ponto-connect/accounts/{accountId}/reauthorization-requests` |
| `integrationAccount` | `list(...)`, `all()` | `GET /ponto-connect/integration-accounts` |
| `usage` | `get(organizationId, month)` | `GET /ponto-connect/organizations/{organizationId}/usage/{month}` |
| `integration` | `delete(organizationId)` | `DELETE /ponto-connect/organizations/{organizationId}/integration` |
| `sandbox` | `listFinancialInstitutionAccounts`, `getFinancialInstitutionAccount` | `GET /ponto-connect/sandbox/financial-institutions/{id}/financial-institution-accounts[/{id}]` |
| `sandbox` | `listFinancialInstitutionTransactions`, `getFinancialInstitutionTransaction`, `createFinancialInstitutionTransaction`, `updateFinancialInstitutionTransaction` | `GET/POST/PATCH .../financial-institution-accounts/{id}/financial-institution-transactions[/{id}]` |
| `webhook` | `keys()` | `GET /webhooks/keys` |

## Architecture

```
config/ponto-connect.php            # Configuration file
src/
  Api/
    PontoConnectApiClient.php       # Saloon connector: mTLS, auth, error mapping, resources
    Requests/                       # One Saloon request per endpoint, grouped per resource
    Resources/                      # Fluent resources returning DTOs and pages
  Data/                             # Readonly DTOs with fromArray()/toArray(), Page
  Enums/                            # Environment, Scope, Language, SynchronizationSubtype, ...
  Exceptions/                       # PontoConnectException, InvalidGrantException, webhook exceptions
  Facades/PontoConnect.php
  Support/                          # JsonApiResource, Decimal, DateParser, Pkce
  Webhooks/                         # WebhookVerifier, WebhookEvent
  Configuration.php                 # Immutable application settings
  PontoConnectManager.php
  PontoConnectServiceProvider.php
```

## Development

```bash
composer install
vendor/bin/phpunit                                  # unit tests (default suite)
vendor/bin/phpstan analyse                          # level 8
vendor/bin/php-cs-fixer fix --config=php-cs-fixer.php
vendor/bin/grumphp run                              # all checks, also run on commit
```

Unit tests use [Orchestra Testbench](https://packages.tools/testbench) and Saloon's `MockClient`, with fixtures copied from the example responses in the API reference (`tests/Fixtures`).

### Sandbox smoke test

`tests/Integration/SandboxSmokeTest.php` calls the real sandbox: it requests a client-credentials token, lists financial institutions and fetches the webhook keys. When `PONTO_CONNECT_TEST_REDIRECT_URI` is set to a registered redirect URI, it also checks that the authorization server accepts the configured scopes. It is not part of the default suite and is skipped unless the `PONTO_CONNECT_*` variables are set:

```bash
PONTO_CONNECT_CLIENT_ID=... PONTO_CONNECT_CLIENT_SECRET=... \
PONTO_CONNECT_CERTIFICATE_PATH=... PONTO_CONNECT_PRIVATE_KEY_PATH=... PONTO_CONNECT_PRIVATE_KEY_PASSPHRASE=... \
vendor/bin/phpunit --testsuite Integration
```

It prints status codes only, never tokens or secrets.

## Official documentation

- [Ponto Connect API reference](https://documentation.ibanity.com/ponto-connect/2/api)
- [Ponto Connect guides](https://documentation.ibanity.com/ponto-connect/2/products)
- [Webhooks](https://documentation.ibanity.com/webhooks) and [security / mTLS](https://documentation.ibanity.com/security)

## License

MIT
