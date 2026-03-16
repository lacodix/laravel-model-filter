---
title: Boolean Filter
weight: 7
---

## Create the filter

```bash
php artisan make:filter PublishedFilter --type=boolean --field=published
```

this creates a filter class that extends BooleanFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\BooleanFilter;

class PublishedFilter extends BooleanFilter
{
    protected string $field = 'published';
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname equals the given boolean value.

You can also use dot-notation for JSON fields, which will be converted to arrow-fields (e.g. `meta->field`).

To use a boolean filter add it to the model like all other filters and
call it with a boolean value:

```php
$filterValues = [
    'published_filter' => true,
];

Post::filter($filterValues)->get();
```

Using a boolean filter with querystring you can call an url like this:

```
https://.../posts?published_filter=1
```

The boolean filter is intended for single boolean fields. For multiple checkboxes use the [Option Filter](option.md).
