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
    public function scopeSearch(Builder $query, string $value, ?array $searchable = null): Builder
    {
        return $query->where(function (Builder $searchQuery) use ($value, $searchable) {
            $this->searchable($searchable)
                ->each(
                    fn (SearchMode $mode, string $field) => $this->applySearchQuery($searchQuery, $field, $mode, $value)
                );
        });
    }

    public function scopeSearchByQueryString(Builder $query): Builder
    {
        $request = Container::getInstance()->make(Request::class);

        return $this->scopeSearch(
            $query,
            $request->only(config('model-filter.search_query_value_name')),
            $request->only(config('model-filter.search_query_fields_name'))
        );
    }

    public function searchable(?array $searchable = null): Collection
    {
        $searchable = $searchable ?? $this->searchable ?? [];

        return collect(
            Arr::isAssoc($searchable) ? $searchable : array_fill_keys($searchable, SearchMode::LIKE)
        );
    }

    protected function applySearchQuery(Builder $query, string $field, SearchMode $mode, string $value): Builder
    {
        return match ($mode) {
            SearchMode::EQUAL => $query->orWhere($field, $value),
            SearchMode::STARTS_WITH => $query->orWhere($field, 'LIKE', $value . '%'),
            SearchMode::ENDS_WITH => $query->orWhere($field, 'LIKE', '%' . $value),
            default => $query->orWhere($field, 'LIKE', '%' . $value . '%'),
        };
    }
}
