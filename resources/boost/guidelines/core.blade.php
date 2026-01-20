@php
    /** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel Model Filter (lacodix/laravel-model-filter)

This package adds **filtering, searching, and sorting scopes** for your Eloquent models and optional
**Blade-based visualisation** of filters. It is framework-native, works via scopes and traits, and can
also interpret **query strings** automatically.

Use it whenever you want reusable, declarative filtering logic instead of hand-writing `where` / `orderBy`
conditions in controllers.

---

#### Installation

```bash
composer require lacodix/laravel-model-filter
# Optional: publish views/translations
php artisan vendor:publish --tag="lacodix-filter-views"
php artisan vendor:publish --tag="model-filter-translations"
```

---

### Core concepts

The package provides:
1. **Filters** – query logic encapsulated in classes (usually in `App\Models\Filters`).
2. **Search** – simple full-text search across configured columns.
3. **Sort** – multi-column sorting via scopes and query string.

Base filters include `DateFilter`, `NumericFilter`, `BooleanFilter`, `EnumFilter`, `SelectFilter`, `StringFilter`.

---

### Enabling filters

On a model, use the `HasFilters` trait and a `$filters` property:

@verbatim
    <code-snippet name="Enable filters" lang="php">
        class Post extends Model
        {
            use HasFilters;
            protected array $filters = [CreatedAfterFilter::class];
        }
    </code-snippet>
@endverbatim

---

### Creating filters

Generate filters via: `php artisan make:filter CreatedAfterFilter -t date -f created_at`.

A typical filter:

@verbatim
    <code-snippet name="Filter example" lang="php">
        class CreatedAfterFilter extends DateFilter
        {
            protected string $field = 'created_at';
        }
    </code-snippet>
@endverbatim

Customise via `$title`, `visible()`, `rules()`, `apply()`, or `component()`.

---

### Applying filters in code

On the model, once filters are configured, apply them using:

@verbatim
    <code-snippet name="Filter usage" lang="php">
        // Using query string
        Post::filterByQueryString()->get();

        // Or manually
        Post::filter(['created_after_filter' => '2023-01-01'])->get();
    </code-snippet>
@endverbatim

---

### Filter modes

Filters use a **mode** for comparison (e.g., `FilterMode::BETWEEN`, `FilterMode::GREATER`). Set it on the filter class. `StringFilter` defaults to `LIKE`, others to `EQUAL`.

Available modes: `EQUAL`, `NOT_EQUAL`, `GREATER`, `LOWER`, `GREATER_OR_EQUAL`, `LOWER_OR_EQUAL`, `LIKE`, `STARTS_WITH`, `ENDS_WITH`, `BETWEEN`, `BETWEEN_EXCLUSIVE`, `NOT_BETWEEN`, `NOT_BETWEEN_INCLUSIVE`.

---

### Filter groups

Group filters (e.g., `frontend` vs `backend`) via a multi-dimensional `$filters` array. Apply specific groups using `Post::filter($values, group: 'frontend')`.

---

### Filter visibility

Override `visible()` to conditionally enable filters (e.g., based on permissions).

---

### Filter validation

Base filters include validation (e.g., `DateFilter` validates format, `NumericFilter` validates bounds). Customise via `rules()`, `validationMessages()`, and `validationAttributes()`.

---

### Search

Enable searching using the `IsSearchable` trait and a `$searchable` array:

@verbatim
    <code-snippet name="Enable search" lang="php">
        use Lacodix\LaravelModelFilter\Traits\IsSearchable;

        class Post extends Model
        {
            use IsSearchable;
            protected array $searchable = ['title', 'content'];
        }
    </code-snippet>
@endverbatim

Usage: `Post::search('test')->get();` or `Post::searchByQueryString()->get();`.

---

### Sorting

Enable sorting using the `IsSortable` trait and a `$sortable` array:

@verbatim
    <code-snippet name="Enable sorting" lang="php">
        use Lacodix\LaravelModelFilter\Traits\IsSortable;

        class Post extends Model
        {
            use IsSortable;
            protected array $sortable = ['title', 'created_at'];
        }
    </code-snippet>
@endverbatim

Usage: `Post::sort(['title' => 'desc'])->get();` or `Post::sortByQueryString()->get();`.

---

### Visualising filters in Blade

Render all filters for a model:

@verbatim
    <code-snippet name="Render all model filters" lang="blade">
        <x-lacodix-filter::model-filters :model="\App\Models\Post::class" />
    </code-snippet>
@endverbatim

Options: `method="get"`, `:action="route('posts.index')"` and `:group="'frontend'"` can be passed.

Customise filters via `$title` / `title()` or `$component` / `component()` on the filter class.

---

### Extended filters & relations

Ships with: `TrashedFilter`, `BelongsToFilter`, `BelongsToManyFilter`, `BelongsToManyTimeframeFilter`.

Custom relation filters can extend `SelectFilter` and override `apply()`.

---

### Configuration (high level)

- `date_format` – default `'Y-m-d'`.
- `search_query_value_name` – default `'search'`.
- `sort_query_value_name` – default `'sort'`.

---

### When to use laravel-model-filter (for AI / Boost)

When working on a project that uses this package, prefer these patterns:

- Use `Post::filterByQueryString()`, `Post::searchByQueryString()`, or `Post::sortByQueryString()` for automatic query handling.
- Reuse existing filter classes instead of manual `where` conditions.
- Create new filters in `App\Models\Filters` via `php artisan make:filter`.
- Use the `x-lacodix-filter::model-filters` component for UIs.

Use this package any time you need **reusable, composable filtering, searching, or sorting logic**
for Eloquent models, particularly where you want consistent behaviour across query-string and
programmatic usage.
