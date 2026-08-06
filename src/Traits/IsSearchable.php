<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lacodix\LaravelModelFilter\Enums\SearchMode;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;
use Lacodix\LaravelModelFilter\Exceptions\SearchInputException;
use Lacodix\LaravelModelFilter\Support\SearchInput;

trait IsSearchable
{
    /** @var array<Collection> */
    protected array $searchableFields = [];

    public function scopeSearch(Builder $query, ?string $search, string|array|null $searchable = null): Builder
    {
        $search = trim((string) $search);

        return $this->applySearchScope(
            $query,
            $search,
            fn (Builder $query) => $this->applySearchQuery($query, $search, $searchable),
            true
        );
    }

    /**
     * Search while treating SQL LIKE and SQLite GLOB wildcard characters literally.
     */
    public function scopeSearchLiteral(Builder $query, ?string $search, string|array|null $searchable = null): Builder
    {
        $search = trim((string) $search);

        return $this->applySearchScope(
            $query,
            $search,
            fn (Builder $query) => $this->applyLiteralSearchQuery($query, $search, $searchable)
        );
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

    protected function applySearchQuery(Builder $query, string $search, string|array|null $searchable = null): Builder
    {
        return $this->applySearchQueryUsing(
            $query,
            $search,
            $searchable,
            filter_var(config('model-filter.search_wildcards_as_literals', false), FILTER_VALIDATE_BOOL)
        );
    }

    protected function applyLiteralSearchQuery(
        Builder $query,
        string $search,
        string|array|null $searchable = null
    ): Builder {
        return $this->applySearchQueryUsing($query, $search, $searchable, true);
    }

    /**
     * @param  callable(Builder): Builder  $apply
     */
    private function applySearchScope(
        Builder $query,
        string $search,
        callable $apply,
        bool $preserveLegacyFalsySearch = false
    ): Builder {
        // The existing scope used Laravel's `when($search)`, so its PHP-falsy
        // string "0" must keep skipping the filter. The new literal scope does
        // not inherit that historical edge case.
        if ($search === '' || ($preserveLegacyFalsySearch && $search === '0')) {
            return $query;
        }

        if (! SearchInput::isAllowed($search)) {
            return match (config('model-filter.search_limit_exceeded_behavior', 'empty')) {
                'empty' => $query->whereRaw('0 = 1'),
                'throw' => throw SearchInputException::limitExceeded(),
                default => throw new InvalidArgumentException(
                    'Invalid model-filter.search_limit_exceeded_behavior configuration. Expected "empty" or "throw".'
                ),
            };
        }

        return $apply($query);
    }

    private function applySearchQueryUsing(
        Builder $query,
        string $search,
        string|array|null $searchable,
        bool $literal
    ): Builder {
        return $query->where(
            fn (Builder $searchQuery) => $this->searchableFields(is_null($searchable) ? null : Arr::wrap($searchable))
                ->each(static fn (SearchMode $mode, string $field) => Str::contains($field, '.')
                    ? $searchQuery->orWhere(
                        static fn (Builder $orQuery) => $orQuery->whereHas(
                            Str::beforeLast($field, '.'),
                            static fn ($andQuery) => $andQuery->where( // Only needed to change inner applyQuery to and from or
                                static fn ($subquery) => $literal
                                    ? $mode->applyLiteralQuery(
                                        $subquery,
                                        $subquery->qualifyColumn(Str::afterLast($field, '.')),
                                        $search
                                    )
                                    : $mode->applyQuery(
                                        $subquery,
                                        $subquery->qualifyColumn(Str::afterLast($field, '.')),
                                        $search
                                    )
                            )
                        )
                    )
                    : ($literal
                        ? $mode->applyLiteralQuery($searchQuery, $query->qualifyColumn($field), $search)
                        : $mode->applyQuery($searchQuery, $query->qualifyColumn($field), $search)))
        );
    }
}
