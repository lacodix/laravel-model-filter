---
title: Select Filter
weight: 4
---

## Create the filter

```bash
php artisan make:filter TestSelectFilter -t select -f fieldname
```

this creates a filter class that extends SelectFilter

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

You can simply use other model ids for the options

```
    public function options(): array
    {
        return OtherModel::query()
            ->pluck('id')
            ->toArray();
    }
```

If you want to use different key and values for the options, keep in mind that the
filters expect the values for filtering in the values of the returned array.
The keys are only used for visualisation in the seleect input fields.

```
    public function options(): array
    {
        return OtherModel::query()
            ->pluck('id', 'title')
            ->toArray();
    }
```

## Filter Modes

Select filters only have the EQUAL mode.