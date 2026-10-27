<?php

namespace App\Enums;

enum BarcodeType: string
{
    case EAN = 'EAN';
    case UPC = 'UPC';
    case GTIN = 'GTIN';
    case CODE128 = 'CODE128';
    case INTERNAL = 'INTERNAL';
}
