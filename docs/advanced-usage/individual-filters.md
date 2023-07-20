---
title: Individual Filters
weight: 1
---

## Create the filter

```bash
php artisan make:filter TestIndividualFilter
```

this creates a filter class that extends the base Filter class. You have to implement the abstract
method "apply" that will be called when the filter is used.

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\Filter;

class TestIndividualFilter extends Filter
{
    public function apply(Builder $query): Builder
    {
        $value = is_array($values) ? current($values) : $values;

        return $query->where('field', $value);
    }
    
    public function populate(string|array $values): static
    {
        $this->values = Arr::wrap($values);

        return $this;
    }
}
```

The filter values are injected inside the filter scopes by calling the populate method of the filter.
The populate method is responsible for getting the data to filter for. This example contains the 
base populate function copied out of the Filter class. You can remove it, if you don't want to change
the behavior.

How the filter cares about its filter data is totally up to you. The above example shows a way of
handling array and string input with one relevant value for the filter.

You can find different populate options in NumericFilter, DateFilter (this both filters care about
ordering of the both input values when populating it) and in the SingleFieldFilter, that takes care
of saving it with the fieldname.<br />
But in the end, it is up to you what happens in populate and apply.

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
    public function apply(Builder $query): Builder
    {
        $value = is_array($values) ? current($values) : $values;

        return $query->where('field', $value);
    }
    
    public function populate(string|array $values): static
    {
        $this->values = Arr::wrap($values);

        return $this;
    }
}
```

The SingleFieldFilter also is based on the default Filter class but it adds a property
for the database fieldname, that can be used in the apply-function. You can find examples
of the SingleFieldFilter in our base classes e.G. SelectFilter or DateFilter.

