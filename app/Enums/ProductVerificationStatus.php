<?php

namespace App\Enums;

enum ProductVerificationStatus: string
{
    case PROVISIONAL = 'PROVISIONAL';
    case PENDING = 'PENDING';
    case VERIFIED = 'VERIFIED';
    case REJECTED = 'REJECTED';
}
