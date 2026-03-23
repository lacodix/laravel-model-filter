---
title: Typed Fluent Filters (PHPStan / Larastan)
weight: 4
---

Factory-based creation exists to improve typed filter creation in fluent/inline definitions.

## Why the factory exists

- `Filter::make(...)` is convenient but cannot always preserve full model/filter type context for static analysis.
- `forModel(...)->make(...)` explicitly binds model and filter types.
- This helps PHPStan / Larastan understand fluent chains better.

## Factory-based creation

```php
EnumFilter::forModel(static::class)
    ->make('status')
    ->setEnum(PostStatus::class)
    ->setQueryName('status')
    ->setTitle('Status');
```

Use this especially in inline model definitions where you want strong type feedback.

## Model return type examples

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    /**
     * @return array<class-string|\Lacodix\LaravelModelFilter\Filters\Filter<static>>
     */
    public function filters(): array
    {
        return [
            DateFilter::forModel(static::class)
                ->make('created_at')
                ->setQueryName('created_after'),
        ];
    }
}
```

## Useful PHPDoc patterns

```php
/**
 * @var \Lacodix\LaravelModelFilter\Filters\EnumFilter<\App\Models\Post> $filter
 */
$filter = EnumFilter::forModel(\App\Models\Post::class)->make('status');
```

### Set `TModel` in your own filter classes

If you create a dedicated filter class, you can bind `TModel` via `@extends`.

```php
<?php

namespace App\Models\Filters;

use App\Models\Post;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

/**
 * @extends DateFilter<Post>
 */
class PublishedAtFilter extends DateFilter
{
    protected string $field = 'published_at';
}
```

For custom filters that directly extend `Filter`, type the builder in `apply()` as well:

```php
<?php

namespace App\Models\Filters;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Filters\Filter;

/**
 * @extends Filter<Post>
 */
class PublishedFilter extends Filter
{
    /**
     * @param Builder<Post> $query
     * @return Builder<Post>
     */
    public function apply(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
```

## When to still prefer a dedicated filter class

- Reusable filter logic across multiple models.
- Complex `apply()` or `populate()` behavior.
- Team-wide shared defaults and conventions.
