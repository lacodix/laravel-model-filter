<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Enums\SearchMode;

trait IsSearchable
{
    public function scopeSearch(Builder $query, ?string $search, ?array $searchable = null): Builder
    {
        return $query->when($search, fn (Builder $query) => $this->applySearchQuery($query, $search, $searchable));
    }

    public function scopeSearchByQueryString(Builder $query): Builder
    {
        $request = Container::getInstance()->make(Request::class);

        return $this->scopeSearch(
            $query,
            $request->get(config('model-filter.search_query_value_name')),
            $request->get(config('model-filter.search_query_fields_name')),
        );
    }

    public function searchable(?array $searchable = null): Collection
    {
        $searchable ??= $this->searchable ?? [];

        return collect(
            Arr::isAssoc($searchable) ? $searchable : array_fill_keys($searchable, SearchMode::LIKE)
        )
            ->only($this->searchableFieldNames() ?? [])
            ->map(static fn ($mode) => is_string($mode) ? SearchMode::fromString($mode) : $mode);
    }

    public function searchableFieldNames(): array
    {
        return Arr::isAssoc($this->searchable) ? array_keys($this->searchable) : $this->searchable;
    }

    protected function applySearchQuery(Builder $query, string $search, ?array $searchable = null): Builder
    {
        return $query->where(
            fn (Builder $searchQuery) => $this->searchable($searchable)
                ->each(static fn (SearchMode $mode, string $field)
                    => $mode->applyQuery($searchQuery, $query->qualifyColumn($field), $search))
        );
    }
}
