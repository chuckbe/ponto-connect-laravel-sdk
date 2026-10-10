<?php

namespace PontoConnect\Exceptions;

use RuntimeException;

/**
 * The webhook signature or one of its claims could not be verified.
 * The request must be rejected and its payload must not be processed.
 */
class WebhookVerificationException extends RuntimeException
{
}
