<?php

namespace App\Enums;

enum WeightResult: string
{
    case PASS = 'PASS';
    case FAIL = 'FAIL';
    case INVALID = 'INVALID';
}
