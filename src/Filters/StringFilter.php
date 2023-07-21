<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class StringFilter extends SingleFieldFilter
{
    public FilterMode $mode = FilterMode::LIKE;

    public function apply(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::EQUAL => $query->where($this->getQualifiedField(), $this->values[$this->field]),
            FilterMode::STARTS_WITH => $query->where($this->getQualifiedField(), 'LIKE', $this->values[$this->field] . '%'),
            FilterMode::ENDS_WITH => $query->where($this->getQualifiedField(), 'LIKE', '%' . $this->values[$this->field]),
            default => $query->where($this->getQualifiedField(), 'LIKE', '%' . $this->values[$this->field] . '%'),
        };
    }
}
