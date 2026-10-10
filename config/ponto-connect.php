<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Either "sandbox" or "live". The API host is the same for both; Ponto
    | routes your calls based on the client certificate. The environment
    | decides which authorization host the customer is redirected to.
    |
    */

    'environment' => env('PONTO_CONNECT_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client Credentials
    |--------------------------------------------------------------------------
    |
    | Found in the security tab of the Ponto Connect product of your
    | application in the Isabel API Hub developer portal.
    |
    */

    'client_id' => env('PONTO_CONNECT_CLIENT_ID'),

    'client_secret' => env('PONTO_CONNECT_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Application ID
    |--------------------------------------------------------------------------
    |
    | Used as the expected "aud" claim when verifying webhook signatures.
    | Defaults to the client ID when left empty.
    |
    */

    'application_id' => env('PONTO_CONNECT_APPLICATION_ID'),

    /*
    |--------------------------------------------------------------------------
    | Mutual TLS
    |--------------------------------------------------------------------------
    |
    | Every request to the Ponto Connect API is authenticated with the TLS
    | certificate generated in the developer portal. Keep these files
    | outside of your repository (e.g. storage/app/private/ponto or a
    | path managed by your server provisioning) and never commit them.
    |
    */

    'certificate_path' => env('PONTO_CONNECT_CERTIFICATE_PATH'),

    'private_key_path' => env('PONTO_CONNECT_PRIVATE_KEY_PATH'),

    'private_key_passphrase' => env('PONTO_CONNECT_PRIVATE_KEY_PASSPHRASE'),

    // Optional CA chain used to verify the API's server certificate.
    // When empty, the system CA bundle is used.
    'ca_chain_path' => env('PONTO_CONNECT_CA_CHAIN_PATH'),

    /*
    |--------------------------------------------------------------------------
    | API URL
    |--------------------------------------------------------------------------
    */

    'api_url' => env('PONTO_CONNECT_API_URL', 'https://api.ibanity.com'),

    /*
    |--------------------------------------------------------------------------
    | Authorization Scopes
    |--------------------------------------------------------------------------
    |
    | The scopes requested in the authorization URL, separated by spaces or
    | commas. "ai" = account information, "pi" = payment initiation,
    | "name" = organization details in the user info endpoint and
    | "offline_access" = a refresh token. Drop "pi" when your Ponto
    | application is not allowed to initiate payments.
    |
    */

    'scopes' => env('PONTO_CONNECT_SCOPES', 'ai pi name offline_access'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests.
    |
    */

    'timeout' => env('PONTO_CONNECT_TIMEOUT', 30),

];
