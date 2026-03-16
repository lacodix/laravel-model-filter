---
title: Option Filter
weight: 8
---

## Create the filter

```bash
php artisan make:filter TestOptionFilter -t option
```

this creates a filter class that extends OptionFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\OptionFilter;

class TestOptionFilter extends OptionFilter
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

The option filter is a base filter that can filter for multiple boolean values, since
it doesn't make sense to add multiple filter classes for multiple checkboxes.

To use an option filter add it to the model like all other filters and
call it with a multidimensional value array:

```php
$filterValues = [
    'test_option_filter' => [
        'published' => true,
        'active' => false,
        'boolvalue' => true,
    ]
];

Post::filter($filterValues)->get();
```
This will filter all Posts that are published, but inactive, and have boolvalue true.

Using an option filter with querystring you can call an url like this:

```
https://.../posts?test_option_filter[published]=1&test_option_filter[active]=0
```

The given value must be castable to a boolean value. This means adding the string "false" would result in true!!

Values that are not of interest can be omitted like it is done with "boolvalue" in the above example.

## Calculated Values

Sometimes you need an option filter but you don't have a boolean database-field. For example
if you want to filter for all open transactions, but you have debit and credit columns. As long this both
columns differ, your transaction is open, as soon as it is the same, it is finished.

To achive such an option filter, you can overwrite the apply-method of the option filter.

```php
<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Filters\OptionFilter;

class TransactionStatus extends OptionFilter
{
    public function options(): array
    {
        return [
            'open' => __('open'),
            'paid' => __('paid'),
        ];
    }

    public function apply(Builder $query): Builder
    {
        if ($this->values['open'] ?? false) {
            $query->whereColumn('debit','<>', 'credit');
        }
        if ($this->values['paid'] ?? false) {
            $query->whereColumn('debit','=', 'credit');
        }

        return $query;
    }
}
```

As you can see, you just need to access $this->values to request the filter value and then you are
able to apply a query of your own.
