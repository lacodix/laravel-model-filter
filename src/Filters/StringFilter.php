<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

/**
 * @template TModel of Model
 */
class StringFilter extends SingleFieldFilter
{
    public FilterMode $mode = FilterMode::LIKE;

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::EQUAL => $query->where($this->getQualifiedField(), $this->values[$this->queryName()]),
            FilterMode::STARTS_WITH => $query->where($this->getQualifiedField(), 'LIKE', $this->values[$this->queryName()] . '%'),
            FilterMode::ENDS_WITH => $query->where($this->getQualifiedField(), 'LIKE', '%' . $this->values[$this->queryName()]),
            default => $query->where($this->getQualifiedField(), 'LIKE', '%' . $this->values[$this->queryName()] . '%'),
        };
    }
}
