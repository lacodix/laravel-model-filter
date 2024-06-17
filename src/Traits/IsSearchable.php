<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Enums\SearchMode;

trait IsSearchable
{
    /** @var array<Collection> $filterInstances  */
    protected array $searchableFields = [];

    public function scopeSearch(Builder $query, ?string $search, ?array $searchable = null): Builder
    {
        $search = trim((string) $search);

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

    public function searchable(): array
    {
        return $this->searchable ?? [];
    }

    public function searchableFields(?array $searchable = null): Collection
    {
        $searchable ??= $this->searchable();

        return collect($searchable)
            ->mapWithKeys(static fn ($value, $key) => is_numeric($key) ? [$value => SearchMode::LIKE] : [$key => $value])
            ->only($this->searchableFieldNames() ?? [])
            ->map(static fn ($mode) => is_string($mode) ? SearchMode::fromString($mode) : $mode);
    }

    public function searchableFieldNames(): array
    {
        return collect($this->searchable())
            ->map(static fn ($value, $key) => is_numeric($key) ? $value : $key)
            ->all();
    }

    protected function applySearchQuery(Builder $query, string $search, ?array $searchable = null): Builder
    {
        return $query->where(
            fn (Builder $searchQuery) => $this->searchableFields($searchable)
                ->each(static fn (SearchMode $mode, string $field) => $mode
                    ->applyQuery($searchQuery, $query->qualifyColumn($field), $search))
        );
    }
}
