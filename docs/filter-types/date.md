---
title: Date Filter
weight: 2
---

## Create the filter

```bash
php artisan make:filter TestDateFilter -t date -f fieldname
```

this creates a filter class the extends DateFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\DateFilter;

class TestDateFilter extends DateFilter
{
    protected string $field = 'fieldname';
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname equals the given value.

## Filter Modes

Default mode is EQUAL

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

class TestDateFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::LOWER;

    protected string $field = 'fieldname';
}
```

Allowed modes are
- FilterMode::LOWER
- FilterMode::GREATER
- FilterMode::LOWER_OR_EQUAL
- FilterMode::GREATER_OR_EQUAL
- FilterMode::EQUAL (default)
- FilterMode::BETWEEN
- FilterMode::BETWEEN_EXCLUSIVE
- FilterMode::NOT_BETWEEN
- FilterMode::NOT_BETWEEN_INCLUSIVE

## Modes with two values

For using the between and not between filters you have to provide two values to the filter. Ordering
of this values doesn't matter, the filter will detect if the first or second is the smaller one.

For providing multiple values use the following query parameters

```
https://.../posts?test_date_filter[]=2023-01-01&test_date_filter[]=2023-01-10
```

or programmatically

```php
Post::filter(['created_at_filter' => ['2023-01-01', '2023-01-10']])->get();
```

This will find all posts created between or not between 1st and 10th of January.

FilterMode::BETWEEN will include both days, FilterMode::BETWEEN_EXCLUSIVE will exclude both days.

FilterMode::NOT_BETWEEN will also exclude posts, that are created on one of both days,

FilterMode::NOT_BETWEEN_INCLUSIVE will include posts that are created on these days.
