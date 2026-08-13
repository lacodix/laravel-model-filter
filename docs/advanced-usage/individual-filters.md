---
title: Individual Filters
weight: 1
---

## Create the filter

```bash
php artisan make:filter TestIndividualFilter
```

this creates a filter class that extends the base Filter class. Implement `applyFilter()` with the
query logic that will be called when the filter is used.

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\Filter;

class TestIndividualFilter extends Filter
{
    public function applyFilter(Builder $query): Builder
    {
        $value = current($this->values);

        return $query->where('field', $value);
    }
}
```

The filter values are injected inside the filter scopes by calling the filter's `populate()` method.
The base implementation is sufficient unless the custom filter needs a structured payload.

How the filter cares about its filter data is totally up to you. The above example shows a way of
handling array and string input with one relevant value for the filter.

You can find different populate options in NumericFilter, DateFilter (this both filters care about
ordering of the both input values when populating it) and in the SingleFieldFilter, that takes care
of saving it with the fieldname.<br />
But in the end, it is up to you what happens in `populate()` and `applyFilter()`.

## Usage of the filter

```
https://.../posts?test_individual_filter=myvalue
```

or

```php
Post::filter(['test_individual_filter' => 'myvalue'])->get()
```

Both examples will result in a string-values parameter on the apply function.

To get an array with multiple values follow the boolean-filter example.

## Filter Modes

Using filter modes is up to you. If it makes sense just use the $mode property like in other base 
filters and apply different queries depending on the mode.

## Single Field Filter

You can additionally use SingleFieldFilter as a base class.

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\SingleFieldFilter;

class TestIndividualFilter extends SingleFieldFilter
{
    public function applyFilter(Builder $query): Builder
    {
        $value = $this->getValue();

        return $query->where('field', $value);
    }
}
```

The SingleFieldFilter also is based on the default Filter class but it adds a property
for the database fieldname, that can be used in the `applyFilter()` function. You can find examples
of the SingleFieldFilter in our base classes e.G. SelectFilter or DateFilter.
