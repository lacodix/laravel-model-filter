<?php

namespace Lacodix\LaravelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelFilter\Enums\FilterMode;

class StringFilter extends SingleFieldFilter
{
    protected FilterMode $mode = FilterMode::LIKE;

    public function apply(Builder $query, string|array $values): Builder
    {
        return $this->query($query, is_array($values) ? current($values) : $values);
    }

    protected function query(Builder $query, string $value): Builder
    {
        return match($this->mode) {
            FilterMode::EQUAL => $query->where($this->field, $value),
            FilterMode::STARTS_WITH => $query->where($this->field, 'LIKE', $value . '%'),
            FilterMode::ENDS_WITH => $query->where($this->field, 'LIKE', '%' . $value),
            default => $query->where($this->field, 'LIKE', '%' . $value . '%'),
        };
    }
}