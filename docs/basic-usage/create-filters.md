---
title: Creating filters
weight: 1
---

There are two recommended ways to create filters:

- `Filter class` / `dedicated filter class` (reusable, project-wide)
- `Fluent filter definition` / `inline filter definition` (local per model)

## Variant A: dedicated filter class

Use a dedicated class when you want to reuse the filter in multiple models or places.

### Create class

```bash
php artisan make:filter CreatedAfterFilter -t date -f created_at
```

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

class CreatedAfterFilter extends DateFilter
{
    protected string $field = 'created_at';

    public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;
}
```

### Register on model

```php
<?php

namespace App\Models;

use App\Models\Filters\CreatedAfterFilter;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    protected array $filters = [
        CreatedAfterFilter::class,
    ];
}
```

### When to use this approach

- Reusable filter logic across multiple models.
- Shared defaults (mode, title, validation, visibility).
- Team-wide consistency in larger projects.

## Variant B: Fluent / inline filter definition

Use an inline definition when the filter is simple and only relevant for one model.

```php
<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    public function filters(): array
    {
        return [
            EnumFilter::forModel(static::class)
                ->make('status')
                ->setTitle('Status')
                ->setQueryName('post_status')
                ->setEnum(PostStatus::class)
                ->setMode(FilterMode::EQUAL),
        ];
    }
}
```
`Factory-based creation` via `forModel(...)->make(...)` is especially useful for typed creation and static analysis. See [Typed fluent filters (PHPStan / Larastan)](../advanced-usage/typed-fluent-filters.md) for details.

You can also use the `make` method without `forModel` if you don't need type-safety.

```php
    public function filters(): array
    {
        return [
            EnumFilter::make('status')
                ->setTitle('Status')
                ->setQueryName('post_status')
                ->setEnum(PostStatus::class)
                ->setMode(FilterMode::EQUAL),
        ];
    }
```

### When to use this approach

- Small, model-local filter definitions.
- Fast customization without creating extra class files.
- Good fit for simple, explicit model configuration.

## Common filter options

This section documents options that are shared across base filters. Filter-specific options are documented in each filter type page.

### `field` / `field()`

- What it does: sets the database field used by single-field filters.
- Availability: single-field filters (for example `DateFilter`, `EnumFilter`, `SelectFilter`, `StringFilter`, `NumericFilter`, `BooleanFilter`).

```php
DateFilter::make('created_at');
// or
DateFilter::make()->field('created_at');
```

### `queryName` / `setQueryName()` / `queryName()`

- What it does: defines the request/query key used for this filter.
- Availability: all filters.
- Default: snake_case class name (or field name for anonymous single-field filters).

```php
DateFilter::make('created_at')->setQueryName('created_after');
Post::filter(['created_after' => '2026-01-01'])->get();
```

### `title` / `setTitle()` / `title()`

- What it does: display title for filter visualisation.
- Availability: all filters.

```php
EnumFilter::make('status')->setTitle('Publication status');
```

### `component` / `setComponent()` / `component()`

- What it does: overrides the filter UI component name.
- Availability: all filters.

```php
DateFilter::make('created_at')->setComponent('date-range');
```

### `mode` / `setMode()`

- What it does: controls comparison behavior (for example `EQUAL`, `BETWEEN`, `CONTAINS`).
- Availability: all filters, but allowed values depend on filter type.

```php
DateFilter::make('created_at')->setMode(FilterMode::BETWEEN);
```

### `visible()`

- What it does: controls whether a filter is visible in the UI.
- Availability: all filters (override method in class-based filters).

```php
public function visible(): bool
{
    return auth()->user()?->can('see-internal-filters') ?? false;
}
```

### `rules()`

- What it does: adds validation rules for incoming filter values.
- Availability: all filters (can be overridden).

```php
public function rules(): array
{
    return [
        $this->queryName() => ['nullable', 'date'],
    ];
}
```

### Validation messages & attributes

- What it does: custom validation text and attribute names.
- Availability: all filters.
- API: use property arrays (`$messages`, `$validationAttributes`) or methods (`messages()`, `validationAttributes()`).

```php
public array $messages = [
    'created_after.date' => 'Please provide a valid date.',
];

public array $validationAttributes = [
    'created_after' => 'created after',
];
```

### `table()`

- What it does: overrides the table used when qualifying columns.
- Availability: single-field filters.

```php
StringFilter::make('title')->table('archived_posts');
```

### `populate()`

- What it does: maps incoming values into the filter's internal value structure.
- Availability: all filters (default implementation is usually enough; override for custom behavior).

```php
public function populate(string|array|null $values): static
{
    return parent::populate($values);
}
```

## Add filter to model

To make a filter usable in a model, add it through `$filters` or the `filters()` method and use the `HasFilters` trait.

```php
<?php

namespace App\Models;

use App\Models\Filters\CreatedAfterFilter;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    protected array $filters = [
        CreatedAfterFilter::class,
    ];
    
    // Alternative solution with method:
    public function filters(): array
    {
        return [
            CreatedAfterFilter::class,
        ];
    }
}
```

## Use the filter

Now you can apply values to the filter and fetch matching models.
The filter key defaults to the class basename in snake case.<br />
CreatedAfterFilter => created_after_filter

To filter programmatically use the `filter` scope.

```php 
Post::filter(['created_after_filter' => '2023-01-01'])->get();
```

## Filter by query string

To filter by query string use the `filterByQueryString` scope.

```php
Post::filterByQueryString()->get();
```

and call the corresponding URL like this

```
https://.../posts?created_after_filter=2023-01-01
```

## Multiple Filters

Filtering with multiple filters is always an `AND` condition. Omit filters that should not be applied.

```php 
Post::filter([
    'created_after_filter' => '2023-01-01',
    'published_filter' => true,
])->get();
```

Or via query string

```
https://.../posts?created_after_filter=2023-01-01&published_filter=1
```

With the same code as for one filter

```php
Post::filterByQueryString()->get();
```
