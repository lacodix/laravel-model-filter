<?php

namespace Lacodix\LaravelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends SingleFieldFilter
{
    public function apply(Builder $query, string|array $values): Builder
    {
        $value = is_array($values) ? current($values) : $values;

        return $query
            ->when(in_array($value, $this->options()), function ($query) use ($value) {
                $query->where($this->field, $value);
            });
    }
}
