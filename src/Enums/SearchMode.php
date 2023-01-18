<?php

namespace Lacodix\LaravelModelFilter\Enums;

use Illuminate\Database\Eloquent\Builder;

enum SearchMode
{
    case EQUAL;
    case LIKE;
    case STARTS_WITH;
    case ENDS_WITH;

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'starts_with' => self::STARTS_WITH,
            'ends_with' => self::ENDS_WITH,
            'equal' => self::EQUAL,
            default => self::LIKE,
        };
    }

    public function applyQuery(Builder $query, string $field, string $search): Builder
    {
        return match ($this) {
            self::EQUAL => $query->orWhere($field, $search),
            self::STARTS_WITH => $query->orWhere($field, 'LIKE', $search . '%'),
            self::ENDS_WITH => $query->orWhere($field, 'LIKE', '%' . $search),
            default => $query->orWhere($field, 'LIKE', '%' . $search . '%'),
        };
    }
}
