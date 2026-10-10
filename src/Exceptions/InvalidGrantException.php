<?php

namespace PontoConnect\Exceptions;

/**
 * The token endpoint rejected the authorization code or refresh token
 * (`invalid_grant`): it was already used, expired after 90 days of
 * inactivity, or the customer revoked the integration in Ponto.
 *
 * The integration can only be restored by a new authorization flow.
 */
class InvalidGrantException extends PontoConnectException
{
}
