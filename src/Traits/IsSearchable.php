<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lacodix\LaravelModelFilter\Contracts\SearchStrategy;
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

    /** @return Collection<string, SearchStrategy> */
    public function searchableFields(?array $searchable = null): Collection
    {
        $declared = $this->searchFieldEntries($this->searchable())
            ->only($this->searchableFieldNames() ?? [])
            ->map(static function ($mode) {
                if (is_string($mode)) {
                    return SearchMode::fromString($mode);
                }

                if (! $mode instanceof SearchStrategy) {
                    throw new InvalidArgumentException('Search fields must declare a SearchMode, mode string or SearchStrategy instance.');
                }

                return $mode;
            });

        if ($searchable === null) {
            return $declared;
        }

        $searchable = array_filter(
            $searchable,
            static fn ($value, $key) => ! is_numeric($key) || $value !== null,
            ARRAY_FILTER_USE_BOTH
        );

        return $this->searchFieldEntries($searchable)
            ->only($declared->keys()->all())
            ->map(static function ($mode, $field) use ($declared) {
                $canonical = $declared->get($field);

                if ($canonical instanceof SearchStrategy && ! $canonical instanceof SearchMode) {
                    return $canonical;
                }

                if (! is_string($mode) && ! $mode instanceof SearchMode) {
                    throw new InvalidArgumentException('Search strategies must be declared on the model.');
                }

                return is_string($mode) ? SearchMode::fromString($mode) : $mode;
            });
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
        return $query->where(function (Builder $searchQuery) use ($query, $search, $searchable, $literal): void {
            $this->searchableFields(is_null($searchable) ? null : Arr::wrap($searchable))
                ->each(static function (SearchStrategy $strategy, string $field) use ($query, $searchQuery, $search, $literal): void {
                    $apply = static fn (Builder $predicate, string $qualifiedField) => $literal
                        ? $strategy->applyLiteralQuery($predicate, $qualifiedField, $search)
                        : $strategy->applyQuery($predicate, $qualifiedField, $search);

                    if (Str::contains($field, '.')) {
                        $searchQuery->orWhere(
                            static fn (Builder $orQuery) => $orQuery->whereHas(
                                Str::beforeLast($field, '.'),
                                static fn (Builder $relationQuery) => $relationQuery->where(
                                    static fn (Builder $predicate) => $apply(
                                        $predicate,
                                        $predicate->qualifyColumn(Str::afterLast($field, '.'))
                                    )
                                )
                            )
                        );

                        return;
                    }

                    // Preserve built-in query shapes; custom AND/OR predicates get their own field group.
                    if ($strategy instanceof SearchMode) {
                        $apply($searchQuery, $query->qualifyColumn($field));

                        return;
                    }

                    $searchQuery->orWhere(
                        static fn (Builder $predicate) => $apply($predicate, $query->qualifyColumn($field))
                    );
                });
        });
    }

    private function searchFieldEntries(array $searchable): Collection
    {
        return collect($searchable)->mapWithKeys(static function ($value, $key) {
            if (! is_numeric($key)) {
                return [$key => $value];
            }

            if (! is_string($value)) {
                throw new InvalidArgumentException('Search field lists must contain field names; strategies require a field key.');
            }

            return [$value => SearchMode::LIKE];
        });
    }
}
