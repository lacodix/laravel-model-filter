<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class BelongsToManyFilter extends BelongsToFilter
{
    public function apply(Builder $query): Builder
    {
        return $query
            ->has($this->field, callback: fn (Builder $query) => $this->getFilterQuery($query));
    }

    protected function getFilterQuery(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::CONTAINS => $this->relationContainsQuery($query),
            default => $this->relationEqualQuery($query),
        };
    }

    protected function relationContainsQuery(Builder $query): Builder
    {
        return $query->whereIn(
            $this->relationQuery()->qualifyColumn($this->idColumn),
            array_intersect($this->filterValues(), $this->options())
        );
    }

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
