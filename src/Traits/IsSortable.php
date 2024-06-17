<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait IsSortable
{
    public function scopeSort(Builder $query, ?array $sort = null): Builder
    {
        return $query->when(
            $sort !== null && $sort !== [] || $this->hasDefaultSorting(),
            fn (Builder $query) => $this->applySortQuery($query, $sort)
        );
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
        return array_keys($this->sortableFields());
    }

    public function sortable(): array
    {
        return $this->sortable ?? [];
    }

    public function sortableFields(): array
    {
        $sortable = $this->sortable();

        if (! Arr::isAssoc($sortable)) {
            return array_fill_keys($sortable, null);
        }

        return $this->fillDirections($sortable);
    }

    public function hasDefaultSorting(): bool
    {
        return Arr::isAssoc($this->sortable());
    }

    protected function applySortQuery(Builder $query, ?array $sort): Builder
    {
        $sortableFields = $this->sortableFields();
        $sort = $this->fillDirections($sort ?? [], 'asc');

        if (! empty($sort)) {
            $sortableFields = array_fill_keys(array_keys($sortableFields), null);
        }

        collect($sortableFields)
            ->merge($sort)
            ->only($this->sortableFieldNames() ?? [])
            ->filter()
            ->map(static fn (string $direction) => strtolower($direction) === 'desc' ? 'desc' : 'asc')
            ->each(static fn (string $direction, string $field) => $query->orderBy($field, $direction));

        return $query;
    }

    protected function fillDirections($fields, ?string $value = null): array
    {
        $results = [];

        foreach ($fields as $col => $dir) {
            if (is_numeric($col)) {
                $results[$dir] = $value;

                continue;
            }

            $results[$col] = $dir;
        }

        return $results;
    }
}
