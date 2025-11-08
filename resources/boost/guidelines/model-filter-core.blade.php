## Laravel Model Filter (lacodix/laravel-model-filter)

This package adds **filtering, searching, and sorting scopes** for your Eloquent models and optional
**Blade-based visualisation** of filters. It is framework-native, works via scopes and traits, and can
also interpret **query strings** automatically.

Use it whenever you want reusable, declarative filtering logic instead of hand-writing `where` / `orderBy`
conditions in controllers.

---

### Requirements & installation

- **PHP**: 8.1+
- **Laravel**: 10+ (for Laravel 9 use v1.x of this package)

Install via Composer:

```bash
composer require lacodix/laravel-model-filter
```

#### Config, views & translations

The package ships a config file, Blade views and translation files.

- Publish filter views (for visualisation):
  ```bash
  php artisan vendor:publish --tag="lacodix-filter-views"
  ```
- Publish translation files (mainly for extended filters like `TrashedFilter`):
  ```bash
  php artisan vendor:publish --tag="model-filter-translations"
  ```

(If you need to customise the config file, publish the package config according to the upstream docs.)

---

### Core concepts

The package gives you three main features:

1. **Filters** – class-based filter objects that encapsulate query logic.
2. **Search** – simple full-text-ish search across configured columns.
3. **Sort** – declarative, multi-column sorting via scopes and query string.

Filters are class-based and usually live in `App\Models\Filters`.  
They extend one of the **base filter types**, e.g.:

- `DateFilter`
- `NumericFilter`
- `BooleanFilter`
- `EnumFilter`
- `SelectFilter`
- `StringFilter`

Plus some **extended filters**, e.g. `TrashedFilter` and more advanced relation filters.

---

### Enabling filters on a model

On a model, you register which filters may be used via a `$filters` property and the `HasFilters` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    protected array $filters = [
        CreatedAfterFilter::class,
        PublishedFilter::class,
        // or grouped filters, see “Filter groups” below
    ];
}
```

You can also use **filter parameters** instead of plain class names when you need to pass options (e.g. titles)
into the filter. See “Filter parameters” below.

---

### Creating filters

Use the artisan command to generate filters:

```bash
php artisan make:filter
```

You’ll be asked interactively which type and options you want, or you can pass options directly, e.g.:

```bash
php artisan make:filter CreatedAfterFilter -t date -f created_at
php artisan make:filter ViewsBetweenFilter -t numeric -f views
```

A typical numeric filter (using the numeric base filter) looks like:

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;

class ViewsBetweenFilter extends NumericFilter
{
    public FilterMode $mode = FilterMode::BETWEEN;

    protected string $field = 'views';
}
```

**Key points for filters**

- Extend the correct base filter (`DateFilter`, `NumericFilter`, `StringFilter`, etc.).
- Set the `$field` property to the **database column** being filtered.
- Optionally override:
  - `$title` or `title()` – label used in visualisation.
  - `visible()` – whether the filter is active (e.g. feature flags).
  - `rules()` / `validationMessages()` / `validationAttributes()` – customise validation.
  - `component` / `component()` – change which Blade component is used for visualisation.
  - `apply(Builder $query)` – for advanced or relation-specific filters.

---

### Applying filters in code

On the model, once filters are configured, you can filter via the scope:

```php
// Manually pass values
Post::filter([
    'created_after_filter' => '2023-01-01',
    'published_filter'     => 1,
])->get();
```

Or have the package read values from the **query string**:

```php
Post::filterByQueryString()->get();
```

For example, this URL:

```txt
https://.../posts?created_after_filter=2023-01-01&published_filter=1
```

…will automatically apply the corresponding filters.

---

### Filter modes

Each filter has a **mode** that controls how comparison is done.  
You can customise it by setting a mode property on the filter class:

```php
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class CreatedAfterFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;

    protected string $field = 'created_at';
}
```

Available modes include:

- `FilterMode::EQUAL`
- `FilterMode::NOT_EQUAL`
- `FilterMode::GREATER`
- `FilterMode::LOWER`
- `FilterMode::GREATER_OR_EQUAL`
- `FilterMode::LOWER_OR_EQUAL`
- `FilterMode::LIKE`
- `FilterMode::STARTS_WITH`
- `FilterMode::ENDS_WITH`
- `FilterMode::BETWEEN`
- `FilterMode::BETWEEN_EXCLUSIVE`
- `FilterMode::NOT_BETWEEN`
- `FilterMode::NOT_BETWEEN_INCLUSIVE`

`StringFilter` defaults to `LIKE`; most others default to `EQUAL`.

---

### Filter groups

You can group filters (e.g. separate **frontend** vs **backend** filters) by using a multi-dimensional
`$filters` array on the model, e.g.:

```php
protected array $filters = [
    'frontend' => [
        PublishedFilter::class,
        PostTypeFilter::class,
    ],
    'backend' => [
        CreatedAfterFilter::class,
        TrashedFilter::class,
    ],
];
```

Then you can choose which group to apply when filtering:

```php
Post::filter($filters, group: 'frontend')->get();
// or, when using query strings, see actual package methods/signature.
```

And in visualisation (see below) you can request a specific group if supported.

---

### Filter visibility

If some filters should only be available under certain conditions (feature flags, permissions, etc.),
override the `visible()` method in your filter:

```php
use Illuminate\Support\Facades\Gate;

class CreatedAfterFilter extends DateFilter
{
    public function visible(): bool
    {
        return Gate::allows('use-date-filters');
    }
}
```

If `visible()` returns `false` the filter is **completely removed** from the model’s filters.

---

### Filter validation

Some base filters include validation out of the box:

- `DateFilter` validates the date format.  
  - The expected format defaults to `'Y-m-d'` and can be overridden via the config value `date_format`.
- `NumericFilter` validates **numeric** input, and supports `$min` / `$max` bounds on the filter class.

You can customise validation by overriding the usual validation methods, for example:

```php
protected function rules(): array
{
    return [
        $this->field => ['nullable', 'numeric'],
    ];
}

protected function validationMessages(): array
{
    return [
        $this->field . '.numeric' => 'The field must be a number.',
    ];
}

protected function validationAttributes(): array
{
    return [
        $this->field => 'Field',
    ];
}
```

Invalid values cause a `ValidationException` to be thrown.

---

### Search

To enable searching on a model, use the `IsSearchable` trait and define a `$searchable` array
(or a `searchable()` method):

```php
use Lacodix\LaravelModelFilter\Traits\IsSearchable;

class Post extends Model
{
    use IsSearchable;

    protected array $searchable = [
        'title',
        'content',
    ];

    // Alternatively:
    public function searchable(): array
    {
        return ['title', 'content'];
    }
}
```

Then you can search either by **code** or **query string**.

Search by code (exact method names may vary slightly in the package):

```php
Post::search('test')->get();
```

Search by query string:

```php
Post::searchByQueryString()->get();
```

URL example:

```txt
https://.../posts?search=test
```

The name of the query parameter is configurable via `model-filter.search_query_value_name` in the config.

---

### Sorting

To enable sortable columns, use the `IsSortable` trait and define a `$sortable` array
(or a `sortable()` method) on the model:

```php
use Lacodix\LaravelModelFilter\Traits\IsSortable;

class Post extends Model
{
    use IsSortable;

    protected array $sortable = [
        'title',
        'created_at',
        'content',
    ];

    public function sortable(): array
    {
        return ['title', 'created_at', 'content'];
    }

    // Optional: integrate with spatie/eloquent-sortable by forwarding
    public function determineOrderColumnName(): string
    {
        return 'order_column';
    }

    public function shouldSortWhenCreating(): bool
    {
        return true;
    }
}
```

Sorting by code:

```php
Post::sort(['title' => 'desc'])->get();
```

Sorting via query string:

```php
Post::sortByQueryString()->get();
```

URL example:

```txt
https://.../posts?sort[title]=desc
```

The sort query parameter name is configurable via `model-filter.sort_query_value_name`.

Sort direction:

- Uses `asc` or `desc` (case-insensitive).
- Direction can be omitted (defaults to `asc`).

You can also define **default sorting** in the model/config so that a fallback order is applied
when no explicit sort is given.

---

### Visualising filters in Blade

The package includes ready-made Blade components to render filter inputs.

To render all filters for a model:

```blade
<x-lacodix-filter::model-filters :model="\App\Models\Post::class" />
```

You can customise HTTP method and action:

```blade
<x-lacodix-filter::model-filters
    :model="\App\Models\Post::class"
    method="get"
    :action="route('posts.index')"
/>
```

Each filter uses a filter-type specific component (e.g. select, text, boolean).  
You can control:

- **Headline / label** via `$title` / `title()` on the filter class.
- **Styling** via CSS classes (e.g. `.filter-container.select` etc.).
- **Component** by setting `$component` or implementing `component()` on the filter class:
  ```php
  protected string $component = 'select';
  // or
  public function component(): string
  {
      return 'my-component';
  }
  ```
  which uses `<x-my-component ...>` in the view.

If needed, publish the views (`lacodix-filter-views` tag) and customise the Blade files in
`resources/views/vendor/lacodix-filter/`.

---

### Extended filters & relations

The package ships **extended filters** that you can add to your `$filters` array directly:

- **`TrashedFilter`** – filters soft-deleted models (`with trashed` / `only trashed`).  
- **BelongsTo / BelongsToMany filters** – filter by related IDs via select options.
- **BelongsToMany timeframe filter** – filter using pivot `start` / `end` timestamps for membership-like relations.
- **Prepopulation helpers** – for mapping / translating options.

You can also build your own **relation filters** by extending `SelectFilter` and overriding
`apply()` to add a `whereHas` / `whereHasMorph` query.

Example (simplified tag filter):

```php
use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class TagsFilter extends SelectFilter
{
    public FilterMode $mode = FilterMode::EQUAL;

    protected string $relation = 'tags';
    protected string $field    = 'tag_id';

    public function options(): array
    {
        return Tag::query()
            ->orderBy('name')
            ->pluck('id', 'name')
            ->toArray();
    }

    public function apply(Builder $query): Builder
    {
        return $query->whereHas($this->relation, function (Builder $query) {
            $query->whereIn($this->field, $this->values);
        });
    }
}
```

---

### Configuration knobs (high level)

- `date_format` – date format for `DateFilter` validation (default `'Y-m-d'`).
- `search_query_value_name` – name of the `search` query parameter.
- `sort_query_value_name` – name of the `sort` query parameter.

(Refer to the package’s config file for the complete list of options.)

---

### When to use laravel-model-filter (for AI / Boost)

When working on a project that uses this package, prefer these patterns:

- **Do not hand-roll ad-hoc `where` conditions** for user-facing filters if equivalent
  **filter classes already exist**. Reuse `Post::filter(...)` / `filterByQueryString()` instead.
- When new filtering behaviour is requested, **create a filter class** under `App\Models\Filters`
  using `php artisan make:filter` and register it in the model’s `$filters` property.
- For search requirements, use `IsSearchable` and `Post::search()` / `searchByQueryString()`;
  don’t implement custom `LIKE` queries in controllers.
- For sorting, use `IsSortable` and `Post::sort()` / `sortByQueryString()` instead of manually
  calling `orderBy()` based on request input.
- Honour existing **filter groups**, **visibility logic**, and **validation rules** when extending
  or generating code.
- When building UIs, prefer the provided Blade components (e.g.
  `<x-lacodix-filter::model-filters :model="Post::class" />`) rather than re-inventing the form,
  unless the project explicitly overrides the visualisation layer.

Use this package any time you need **reusable, composable filtering, searching, or sorting logic**
for Eloquent models, particularly where you want consistent behaviour across query-string and
programmatic usage.
