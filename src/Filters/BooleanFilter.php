<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends SingleFieldFilter<TModel>
 */
class BooleanFilter extends SingleFieldFilter
{
    protected string $component = 'boolean';

    public function apply(Builder $query): Builder
    {
        if (is_null($this->getValue())) {
            return $query;
        }

        return $query->where($this->getQualifiedField(), (bool) ($this->getValue()));
    }
}
