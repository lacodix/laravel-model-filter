---
title: Filter Modes
weight: 2
---

All filters have a mode for filtering, that can change the behaviour of the filter.
The default mode of almost all filters is filtering for "equal" values. The default mode
for a string filter is "like".

To change the mode of a filter, just insert the mode propety to the filter:

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

class CreatedAfterFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;

    protected string $field = 'created_at';
}
```

There are several filter modes available, but not all modes make sense with all filter types. You will 
find the usable modes for each filter type in the filter types section of this documentation

All available modes are:
- FilterMode::EQUAL;
- FilterMode::NOT_EQUAL;
- FilterMode::GREATER;
- FilterMode::LOWER;
- FilterMode::GREATER_OR_EQUAL;
- FilterMode::LOWER_OR_EQUAL;
- FilterMode::LIKE;
- FilterMode::STARTS_WITH;
- FilterMode::ENDS_WITH;
- FilterMode::BETWEEN;
- FilterMode::BETWEEN_EXCLUSIVE;
- FilterMode::NOT_BETWEEN;
- FilterMode::NOT_BETWEEN_INCLUSIVE;
