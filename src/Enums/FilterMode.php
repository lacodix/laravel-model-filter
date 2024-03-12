<?php

namespace Lacodix\LaravelModelFilter\Enums;

enum FilterMode
{
    case EQUAL;
    case NOT_EQUAL;
    case GREATER;
    case LOWER;
    case GREATER_OR_EQUAL;
    case LOWER_OR_EQUAL;
    case LIKE;
    case STARTS_WITH;
    case ENDS_WITH;
    case BETWEEN;
    case BETWEEN_EXCLUSIVE;
    case NOT_BETWEEN;
    case NOT_BETWEEN_INCLUSIVE;
    case CONTAINS;

    public function needsMultipleValues(): bool
    {
        return match ($this) {
            self::BETWEEN,
            self::BETWEEN_EXCLUSIVE,
            self::NOT_BETWEEN,
            self::NOT_BETWEEN_INCLUSIVE,
            self::CONTAINS => true,
            default => false,
        };
    }
}
