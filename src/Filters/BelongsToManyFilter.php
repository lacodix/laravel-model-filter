<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

/**
 * @template TModel of Model
 */
class BelongsToManyFilter extends BelongsToFilter
{
    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        return $query
            ->has($this->field, callback: fn (Builder $query) => $this->getFilterQuery($query));
    }

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    protected function getFilterQuery(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::CONTAINS => $this->relationContainsQuery($query),
            default => $this->relationEqualQuery($query),
        };
    }

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    protected function relationContainsQuery(Builder $query): Builder
    {
        return $query->whereIn(
            $this->relationQuery()->qualifyColumn($this->idColumn),
            array_intersect($this->filterValues(), $this->options())
        );
    }

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    protected function relationEqualQuery(Builder $query): Builder
    {
        return $query
            ->when(
                in_array($this->filterValues(), $this->options()),
                fn ($query) => $query->where(
                    $this->relationQuery()->qualifyColumn($this->idColumn),
                    $this->filterValues()
                )
            );
    }

    protected function filterValues(): mixed
    {
        return $this->values[$this->field];
    }
}
