---
title: Select Filter
weight: 4
---

## Create the filter

```bash
php artisan make:filter TestSelectFilter -t select -f fieldname
```

this creates a filter class the extends SelectFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\SelectFilter;

class TestSelectFilter extends SelectFilter
{
    protected string $field = 'fieldname';

    public function options(): array
    {
        // add the allowed values here
        return [
            'value1',
            'value2',
            ...
        ];
    }
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname equals the given value.

The options function must be implemented, given values for the filter will be
removed if not contained in this array.

## Filter Modes

Select filters only have the EQUAL mode.