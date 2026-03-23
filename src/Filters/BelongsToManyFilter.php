<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

/**
 * @template TModel of Model
 *
 * @extends BelongsToFilter<TModel>
 */
class BelongsToManyFilter extends BelongsToFilter
{
    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function applyFilter(Builder $query): Builder
    {
        return $query
            ->when(
                $this->mode === FilterMode::NOT_CONTAINS,
                fn (Builder $query) => $query->whereDoesntHave($this->field, callback: fn (Builder $query) => $this->relationContainsQuery($query)),
                fn (Builder $query) => $query->has($this->field, callback: fn (Builder $query) => $this->getFilterQuery($query))
            );
    }

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    protected function getFilterQuery(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::CONTAINS => $this->relationContainsQuery($query),
            FilterMode::NOT_CONTAINS => $this->relationNotContainsQuery($query),
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
            array_intersect(Arr::wrap($this->filterValues()), $this->options())
        );
    }

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    protected function relationNotContainsQuery(Builder $query): Builder
    {
        return $query->whereNotIn(
            $this->relationQuery()->qualifyColumn($this->idColumn),
            array_intersect(Arr::wrap($this->filterValues()), $this->options())
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
        return $this->getValue();
    }
}
