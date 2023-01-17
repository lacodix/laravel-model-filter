<?php

namespace Lacodix\LaravelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lacodix\LaravelFilter\Filters\Filter;

trait HasFilters
{
    protected Collection $filterInstances;

    public function scopeFilter(Builder $query, array $values): Builder
    {
        $values = collect($values)
            ->only($this->filterQueryNames())
            ->filter();

        $this->filters()
            ->filter(
                static fn (Filter $filter, string|int $key) => $values->has($filter->queryName($key))
            )
            ->each(
                static fn (Filter $filter, string|int $key) => $filter
                    ->apply($query, $values->get($filter->queryName($key)))
            );

        return $query;
    }

    public function scopeFilterByQueryString(Builder $query): Builder
    {
        $request = Container::getInstance()->make(Request::class);

        return $this->scopeFilter($query, $request->all());
    }

    public function filters(): Collection
    {
        return $this->filterInstances ??= collect($this->filters ?? [])
            ->map(static fn ($filterName) => $filterName instanceof Filter ? $filterName : new $filterName());
    }

    protected function filterQueryNames()
    {
        return $this
            ->filters()
            ->map(static fn (Filter $filter, string|int $key) => $filter->queryName($key))->values()->all();
    }
}
