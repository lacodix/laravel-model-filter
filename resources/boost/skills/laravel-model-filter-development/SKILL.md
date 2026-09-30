---
name: laravel-model-filter-development
description: >-
  Use when building or changing Eloquent model filters, search, sorting,
  query-string scopes, Blade filter forms, relation or timeframe filters,
  validation, or filter SQL tests with lacodix/laravel-model-filter.
---

# Laravel Model Filter development

Use the package's scopes and filter classes for reusable Eloquent filtering. Check the installed package version and existing model filters before changing a query. The linked [public documentation](https://github.com/lacodix/laravel-model-filter/tree/master/docs) describes the current APIs; check the installed source if its version differs.

## Workflow

1. For a reusable filter, generate a class with `php artisan make:filter CreatedAfterFilter -t date -f created_at`, then register it on a model using `HasFilters` and `$filters`. For a model-local filter, return a configured filter instance from `filters()`. The default request key is the snake-case class name, even for an instance: `EnumFilter::make('status')` reads `enum_filter`. Use `->setQueryName('status')`, and give same-class instances distinct query names. [Create filters](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/create-filters.md)
2. Apply values with `Post::filter(['created_after_filter' => '2023-01-01'])` or read request values with `Post::filterByQueryString()`. Grouped filters use a keyed `$filters` array and a group argument; the default group is `__default`. `visible()` removes unavailable filters from the model's usable filters. [Groups](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/filter-groups.md) · [Visibility](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/filter-visibility.md)
3. Choose a `FilterMode` supported by the filter type. `StringFilter` defaults to `LIKE`; most others default to `EQUAL`. Built-in filters validate input shape. Invalid values are skipped with `ValidationMode::FILTER` and throw with `ValidationMode::THROW`; customize `rules()`, `messages()` and `validationAttributes()` as needed. [Modes](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/filter-modes.md) · [Validation](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/filter-validation.md) · [Input shapes](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/filter-input-contract.md)
4. For search, add `IsSearchable` and `$searchable`; use `search()` or `searchByQueryString()`. For ordinary text where wildcard characters must be literal, use `searchLiteral()` or configure `search_wildcards_as_literals`. For sorting, add `IsSortable` and `$sortable`; use `sort()` or `sortByQueryString()`. Query-string search and sort only use configured fields. [Search](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/search.md) · [Sort](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/sort.md)
5. Render the built-in form with `<x-lacodix-filter::model-filters :model="\App\Models\Post::class" />`; configure its method, action, and group as needed. Set a filter's title or component for UI changes; publish views or translations if customization requires them. [Visualisation](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/filter-visualisation.md) · [Installation](https://github.com/lacodix/laravel-model-filter/blob/master/docs/installation.md)

## Easy-to-miss cases

- For custom query logic, implement `applyFilter(Builder $query)` so the base `apply()` input guard stays in place. [Individual filters](https://github.com/lacodix/laravel-model-filter/blob/master/docs/advanced-usage/individual-filters.md)
- For relationship selection or soft-deleted records, check the shipped `BelongsToFilter`, `BelongsToManyFilter`, and `TrashedFilter` before writing a custom filter. [Filter types](https://github.com/lacodix/laravel-model-filter/blob/master/docs/filter-types/overview.md)
- `RunsOnRelation` wraps filter logic in `whereHas`, including dot-notated relations. Put custom query logic in `applyFilter(Builder $query)`, not an `apply()` override, so the trait's relation and input handling run. [Relations](https://github.com/lacodix/laravel-model-filter/blob/master/docs/extended-filters/runs-on-relation.md)
- `BelongsToManyTimeframeFilter` has a separate `TimeframeFilterMode`. `NEVER` and `NOT_CURRENT` invert the relation test; with no selected values they mean no relation ever and no currently active relation, respectively. Check the payload and date precision before building the UI or query. For custom inversion in a `BelongsToManyFilter` subclass, use its `isInverted()` and `applyInvertedFilter()` hooks. [Timeframe filter](https://github.com/lacodix/laravel-model-filter/blob/master/docs/extended-filters/belongs_to_many_timeframe.md)
- `EnumFilter` sorts options by their backed values by default. Use `setSortedOptions(false)` to retain enum declaration order. [Enum filter](https://github.com/lacodix/laravel-model-filter/blob/master/docs/filter-types/enum.md)
- Test SQL and bindings without executing a query using `FilterAssert::shape()` for structural checks or `FilterAssert::equals()` for exact SQL and bindings. [Filter assertions](https://github.com/lacodix/laravel-model-filter/blob/master/docs/testing/__index.md)
