<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait IsSortable
{
    public function scopeSort(Builder $query, ?array $sort): Builder
    {
        return $query->when(! empty($sort), fn (Builder $query) => $this->applySortQuery($query, $sort));
    }

    public function scopeSortByQueryString(Builder $query): Builder
    {
        $request = Container::getInstance()->make(Request::class);

        return $this->scopeSort(
            $query,
            $request->get(config('model-filter.sort_query_value_name')),
        );
    }

    public function sortableFieldNames(): array
    {
        return Arr::isAssoc($this->sortable) ? array_keys($this->sortable) : $this->sortable;
    }

    protected function applySortQuery(Builder $query, array $sort): Builder
    {
        $sort = Arr::isAssoc($sort) ? $sort : array_fill_keys($sort, 'asc');

        collect($sort)
            ->only($this->sortableFieldNames() ?? [])
            ->map(static fn (string $direction) => strtolower($direction) === 'desc' ? 'desc' : 'asc')
            ->each(static fn (string $direction, string $field) => $query->orderBy($field, $direction));

        return $query;
    }
}
