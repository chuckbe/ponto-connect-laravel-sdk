<?php

namespace PontoConnect\Enums;

enum SynchronizationSubtype: string
{
    case ACCOUNT_DETAILS = 'accountDetails';
    case ACCOUNT_TRANSACTIONS = 'accountTransactions';
    case ACCOUNT_TRANSACTIONS_WITH_UNSETTLED = 'accountTransactionsWithUnsettled';
}
