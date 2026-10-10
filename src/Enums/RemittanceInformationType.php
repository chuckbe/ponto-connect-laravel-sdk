<?php

namespace PontoConnect\Enums;

enum RemittanceInformationType: string
{
    case STRUCTURED = 'structured';
    case UNSTRUCTURED = 'unstructured';
}
