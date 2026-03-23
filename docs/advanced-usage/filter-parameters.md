---
title: Filter Parameters
weight: 3
---

For core filter creation and shared filter options (`queryName`, `title`, `mode`, `component`, validation, visibility), see [Creating filters](../basic-usage/create-filters.md).

This page focuses on parameterizing filter instances directly in the model.

When adding filters to models you usually use the `$filters` property:

```php
    protected array $filters = [
        CreatedAfterFilter::class,
    ];
```

With this solution you are not able to set properties on a filter instance. But sometimes you need multiple almost
identical filters, or you just want to use different headlines for the same filter when using it in different
models. It would be kind of waste to create multiple filter classes for that.

As an alternative you can just use the filters() method of the HasFilters trait.

```php
<?php

namespace App\Models;

use App\Models\Filters\CreatedAfterFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    public function filters(): array
    {
        return [
            new CreatedAfterFilter()
        ];
    }
}
```

With that solution you can instantiate filters and call fluent setters on them.

```php
    public function filters(): array
    {
        return [
            CreatedAfterFilter::make()
                ->setQueryName('my_query_string')
                ->setMode(FilterMode::LOWER)
                ->setValidationMode(ValidationMode::THROW)
                ->setTitle(__('My Headline'))
                ->setComponent('date'),
        ];
    }
```

In your own filter classes you can add custom fluent setters by returning `static`.

All filters are makeable, so you can use `Filter::make($arguments...)` instead of `new Filter($arguments)`.

For typed factory-based fluent creation (`forModel(...)->make(...)`) and PHPStan/Larastan context, see [Typed fluent filters (PHPStan / Larastan)](./typed-fluent-filters.md).

## Use base filters inline

With this option you can just use some available base filters without creating your
own classes. For the date, string and boolean-filter you don't need to create dedicated
filter classes. 

Filter classes have a huge benefit if you can reuse it like an reusable 
created_at filter, it is only created once and can be applied to multiple models.


```php
    public function filters(): array
    {
        return [
            DateFilter::make('created_at')
                ->setTitle('Created between')
                ->setQueryName('created_at_between')
                ->setMode(FilterMode::BETWEEN),

            StringFilter::make('title')
                ->setTitle('Title')
                ->setQueryName('title_starts_with')
                ->setMode(FilterMode::STARTS_WITH),

            NumericFilter::make('counter')
                ->setTitle('Count max')
                ->setQueryName('counter_lower_filter')
                ->setMode(FilterMode::LOWER_OR_EQUAL),
        ];
    }
```

To apply this filters the keys are equal to the query names.

```php 
Post::filter([
    'created_at_between' => ['2023-01-01', '2023-01-31'],
    'title_starts_with' => 'test',
    'counter_lower_filter' => 500,
])->get();
```

or open the url

```
https://.../posts?created_at_between[]=2023-01-01&created_at_between[]=2023-01-31&title_starts_with=test&counter_lower_filter=500
```
