<?php

namespace PontoConnect\Enums;

enum SynchronizationStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case ERROR = 'error';
}
