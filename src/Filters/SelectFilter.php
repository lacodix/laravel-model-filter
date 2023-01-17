<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends SingleFieldFilter
{
    public function apply(Builder $query, string|array $values): Builder
    {
        $value = is_array($values) ? current($values) : $values;

        return $query
            ->when(in_array($value, $this->options()), fn ($query) => $query->where($this->field, $value));
    }
}
