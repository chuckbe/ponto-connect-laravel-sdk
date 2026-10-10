<?php

namespace PontoConnect\Enums;

/**
 * Ponto Connect webhook event types (API reference, "Webhook Resources").
 */
enum WebhookEventType: string
{
    case SYNCHRONIZATION_SUCCEEDED_WITHOUT_CHANGE = 'pontoConnect.synchronization.succeededWithoutChange';
    case SYNCHRONIZATION_FAILED = 'pontoConnect.synchronization.failed';
    case ACCOUNT_DETAILS_UPDATED = 'pontoConnect.account.detailsUpdated';
    case ACCOUNT_TRANSACTIONS_CREATED = 'pontoConnect.account.transactionsCreated';
    case ACCOUNT_TRANSACTIONS_UPDATED = 'pontoConnect.account.transactionsUpdated';
    case ACCOUNT_PENDING_TRANSACTIONS_CREATED = 'pontoConnect.account.pendingTransactionsCreated';
    case ACCOUNT_PENDING_TRANSACTIONS_UPDATED = 'pontoConnect.account.pendingTransactionsUpdated';
    case ACCOUNT_REAUTHORIZED = 'pontoConnect.account.reauthorized';
    case ORGANIZATION_BLOCKED = 'pontoConnect.organization.blocked';
    case ORGANIZATION_UNBLOCKED = 'pontoConnect.organization.unblocked';
    case INTEGRATION_ACCOUNT_ADDED = 'pontoConnect.integration.accountAdded';
    case INTEGRATION_ACCOUNT_REVOKED = 'pontoConnect.integration.accountRevoked';
    case INTEGRATION_CREATED = 'pontoConnect.integration.created';
    case INTEGRATION_REVOKED = 'pontoConnect.integration.revoked';
    case PAYMENT_REQUEST_CLOSED = 'pontoConnect.paymentRequest.closed';
}
