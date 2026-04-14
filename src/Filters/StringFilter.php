<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

/**
 * @template TModel of Model
 *
 * @extends SingleFieldFilter<TModel>
 */
class StringFilter extends SingleFieldFilter
{
    public FilterMode $mode = FilterMode::LIKE;

    /**
     * @param  Builder<TModel> $query
     *
     * @return Builder<TModel>
     */
    public function applyFilter(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::EQUAL => $query->where($this->getQualifiedField(), $this->getValue()),
            FilterMode::STARTS_WITH => $query->where($this->getQualifiedField(), 'LIKE', $this->getValue() . '%'),
            FilterMode::ENDS_WITH => $query->where($this->getQualifiedField(), 'LIKE', '%' . $this->getValue()),
            default => $query->where($this->getQualifiedField(), 'LIKE', '%' . $this->getValue() . '%'),
        };
    }
}
