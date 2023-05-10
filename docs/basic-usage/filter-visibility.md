---
title: Filter Visibility
weight: 4
---

Sometimes it is necessary to hide some of your filters. Maybe you want to use Laravel Penant
or similar packages to activate and deactivate features in your application. In this cases
it might be suitable to remove filters that only makes sense with that features activated.

In such cases you can overwrite the visible-method of each filter

## Set visibility

```php 
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Laravel\Pennant\Feature;

class CreatedAfterFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;

    protected string $field = 'created_at';
    
    public function visible(): bool
    {
        return Feature::active('date_filters');
    }
}
```

If Feature `date_filters` isn't active in this example the CreatedAfterFilter will completely
be removed from the models filters.