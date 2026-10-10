<?php

namespace PontoConnect\Enums;

enum Scope: string
{
    case ACCOUNT_INFORMATION = 'ai';
    case PAYMENT_INITIATION = 'pi';
    case NAME = 'name';
    case OFFLINE_ACCESS = 'offline_access';
}
