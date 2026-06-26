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

## Filtering for NULL values

Select-based filters (including `BelongsToFilter` and `EnumFilter`) only filter for the
values returned by `options()`. Nullable columns that contain no value can therefore not
be targeted out of the box, because there is no option representing "empty".

Enable the `nullable()` option to add a dedicated entry that filters for records where the
field is `NULL`:

```php
class TestSelectFilter extends SelectFilter
{
    protected string $field = 'fieldname';

    protected bool $nullable = true;

    public function options(): array
    {
        return ['value1', 'value2'];
    }
}
```

You can also enable it fluently when registering the filter, optionally with a custom label:

```php
(new TestSelectFilter())->nullable();
(new TestSelectFilter())->nullable(label: 'Without value');
```

When enabled, an additional option with the reserved value `__null__` is exposed. Selecting
it applies a `whereNull()` (or, in the multiselect modes, `orWhereNull()` /
`whereNotNull()`) on the field. The label defaults to the translation
`model-filter::filters.none` ("None" / "Keine") and can be overridden via the `label`
argument of `nullable()`.

> The added option is only rendered/validated through `optionsWithNull()`. Your own
> `options()` implementation stays untouched and must **not** include the `__null__` value
> itself. The reserved value `__null__` should not be used as a real option value.

`nullable()` is opt-in and defaults to `false`, so existing filters keep their exact
behaviour unless you turn it on.

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
