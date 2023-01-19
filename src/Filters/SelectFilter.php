<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends SingleFieldFilter
{
    protected function query(Builder $query, array $values): Builder
    {
        return $query
            ->when(
                in_array($values[$this->field], $this->options()),
                fn ($query) => $query->where($this->field, $values[$this->field])
            );
    }
}
