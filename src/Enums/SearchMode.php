<?php

namespace Lacodix\LaravelModelFilter\Enums;

enum SearchMode
{
    case EQUAL;
    case LIKE;
    case STARTS_WITH;
    case ENDS_WITH;

    public function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'starts_with' => self::STARTS_WITH,
            'ends_with' => self::ENDS_WITH,
            'equal' => self::EQUAL,
            default => self::LIKE,
        };
    }
}
