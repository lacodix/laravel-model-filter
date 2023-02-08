---
title: Boolean Filter
weight: 7
---

## Create the filter

```bash
php artisan make:filter TestBooleanFilter -t boolean
```

this creates a filter class the extends BooleanFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\BooleanFilter;

class TestBooleanFilter extends BooleanFilter
{
    public function options(): array
    {
        return [
            // add all boolean database columns here to filter for 
            'published',
            'active',
            'boolvalue',
        ];
    }
}
```

The boolean filter is the only base filter that can filter for multiple values, since
it doesn't make sense to add multiple filter classes for multiple checkboxes.
Nevertheless you are allowed to only add one single field to the options array,
if you want to filter only for one boolean value with this filter.

To use a boolean filter add it to the model like all other filters and
call it with a multidimensional value array:

```php
$filterValues = [
    'test_boolean_filter' => [
        'published' => true,
        'active' => false,
        'boolvalue' => true,
    ]
];

Post::filter($filterValues)->get();
```
This will filter all Posts that are published, but inactive, and have boolvalue true.

Using a boolean filter with querystring you can call an url like this:

```
https://.../posts?test_boolean_filter[published]=1&test_boolean_filter[active]=0
```

The given value must be castable to a boolean value. This means adding the string "false" would result in true!!

Values that are not of interest can be omitted like it is done with "boolvalue" in the above example.
