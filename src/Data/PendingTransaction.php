<?php

namespace PontoConnect\Data;

/**
 * A transaction the bank has not fully processed yet.
 *
 * Pending transactions are informational only: once booked they disappear
 * and come back as a {@see Transaction} with a different id, so replace the
 * stored pending transactions of an account each time you list them.
 */
final class PendingTransaction extends Transaction
{
}
