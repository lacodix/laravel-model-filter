<?php

namespace Lacodix\LaravelModelFilter\Enums;

use Carbon\Carbon;

enum TimeframeFilterPrecision
{
    case DAY;
    case MONTH;
    case YEAR;

    public function getPrecisionDate(Carbon $date): string
    {
        return match ($this) {
            self::DAY => $date->format('Y-m-d'),
            self::MONTH => $date->format('Y-m'),
            self::YEAR => $date->format('Y'),
        };
    }
}
