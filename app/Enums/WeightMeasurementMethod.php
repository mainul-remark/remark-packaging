<?php

namespace App\Enums;

enum WeightMeasurementMethod: string
{
    case UNIT_SAMPLE = 'UNIT_SAMPLE';
    case DECLARED = 'DECLARED';
    case SCALE_CAPTURED = 'SCALE_CAPTURED';
}
