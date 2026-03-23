---
title: Select Filter
weight: 4
---

General setup (creation, fluent definition, `queryName`, `title`, `mode`, validation, visibility) is documented in [Creating filters](../basic-usage/create-filters.md).

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

You can also use dot-notation for JSON fields, which will be converted to arrow-fields (e.g. `meta->field`).

The `options()` method must be implemented, given values for the filter will be
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
The keys are only used for visualisation in the select input fields.

```
    public function options(): array
    {
        return OtherModel::query()
            ->pluck('id', 'title')
            ->toArray();
    }
```

## Multiselect modes

If you want to give multiple values to filter for, you can set the mode to CONTAINS

```
public FilterMode $mode = FilterMode::CONTAINS;
```

With this mode you can filter for multiple values. All models that fit to one of the given options will be found. It
is still comparing the model column against the given filter values. To get it working in your views, you have to 
name the input element as an array, see select.blade.php for an example when using the multiple option.

Allowed modes are
- FilterMode::EQUAL
- FilterMode::CONTAINS
- FilterMode::NOT_CONTAINS
