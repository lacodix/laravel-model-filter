---
title: Numeric Filter
weight: 6
---

## Create the filter

```bash
php artisan make:filter TestNumericFilter -t numeric -f fieldname
```

this creates a filter class the extends NumericFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\NumericFilter;

class TestNumericFilter extends NumericFilter
{
    protected string $field = 'fieldname';
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname equals the given value.

## Filter Modes

Default mode is EQUAL

Change filter mode:

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;

class TestNumericFilter extends NumericFilter
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

For using between and not between filters you have to provide two values to the filter. Ordering
of this values doesn't matter, the filter will detect if the first or second is the smaller one.

For providing multiple values use the following query parameters

```
https://.../posts?test_numeric_filter[]=100&test_date_filter[]=1000
```

or programmatically

```php
Post::filter(['test_numeric_filter' => [100, 1000]])->get();
```

This will find all posts where fieldname is between 100 and 1000.

FilterMode::BETWEEN will include both values, FilterMode::BETWEEN_EXCLUSIVE will exclude both values.

FilterMode::NOT_BETWEEN will also exclude posts, that have one of the both values in the selected field,

FilterMode::NOT_BETWEEN_INCLUSIVE will include posts that have these values.
