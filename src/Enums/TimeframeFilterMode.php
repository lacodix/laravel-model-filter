<?php

namespace Lacodix\LaravelModelFilter\Enums;

enum TimeframeFilterMode: string
{
    case CURRENT = 'current';
    case EVER = 'ever';
    case TIMEFRAME = 'timeframe';
    case START_IN_TIMEFRAME = 'start_in_timeframe';
    case END_IN_TIMEFRAME = 'end_in_timeframe';
    case NEVER = 'never';
    case NOT_CURRENT = 'not_current';

    public function needsDateValues(): bool
    {
        return match ($this) {
            self::TIMEFRAME,
            self::START_IN_TIMEFRAME,
            self::END_IN_TIMEFRAME => true,
            default => false,
        };
    }

    public function isInverted(): bool
    {
        return match ($this) {
            self::NEVER,
            self::NOT_CURRENT => true,
            default => false,
        };
    }
}
