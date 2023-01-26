<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class StringFilter extends SingleFieldFilter
{
    public FilterMode $mode = FilterMode::LIKE;

    protected function query(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::EQUAL => $query->where($this->field, $this->values[$this->field]),
            FilterMode::STARTS_WITH => $query->where($this->field, 'LIKE', $this->values[$this->field] . '%'),
            FilterMode::ENDS_WITH => $query->where($this->field, 'LIKE', '%' . $this->values[$this->field]),
            default => $query->where($this->field, 'LIKE', '%' . $this->values[$this->field] . '%'),
        };
    }
}
