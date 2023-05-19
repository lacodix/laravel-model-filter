---
title: String Filter
weight: 3
---

## Create the filter

```bash
php artisan make:filter TestStringFilter -t string -f fieldname
```

this creates a filter class that extends StringFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\StringFilter;

class TestStringFilter extends StringFilter
{
    protected string $field = 'fieldname';
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname contains the given value.

## Filter Modes

Default mode is LIKE

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

class TestDateFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::STARTS_WITH;

    protected string $field = 'fieldname';
}
```

Allowed modes are
- FilterMode::STARTS_WITH;
- FilterMode::ENDS_WITH;
- FilterMode::EQUAL;
- FilterMode::LIKE (default);
