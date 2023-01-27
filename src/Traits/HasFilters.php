<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Filters\Filter;

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
                static fn (Filter $filter)
                    => $values->has($filter->getQueryName()) && $filter->applicable()
            )
            ->each(
                static fn (Filter $filter) => $filter
                    ->values($values->get($filter->getQueryName()))
                    ->when(
                        $filter->validationMode === ValidationMode::THROW,
                        fn (Filter $filter) => $filter->validate()
                    )
                    ->when(
                        $filter->validationMode === ValidationMode::THROW || ! $filter->fails(),
                        fn (Filter $filter) => $filter->apply($query)
                    )
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
            ->map(static fn (Filter $filter) => $filter->getQueryName())->values()->all();
    }
}
