---
title: Basic Usage
weight: 1
---

Create your first filter

```bash
php artisan make:filter CreatedAfterFilter -t date -f created_at
```

This filter will be created as the Class CreatedAfterFilter.php in the folder app/Models/Filters.
It is a filter of type 'date' and will be bound to database field 'created_at'. It can be used in
all Models, that have a created_at datetime field.

All filters have a mode for filtering, that can change the behaviour of the filter.
The default mode of almost all filters is filtering for "equal" values. The default mode
for a string filter is "like". Since CreatedAfterFilter shall find all models that are
created AFTER a given date, the Mode must be changed.

Open up the CreatedAfterFilter file and add the correct mode

```php 
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\DateFilter;

class CreatedAfterFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;

    protected string $field = 'created_at';
}
```

To make this filter usable in a model just add a $filters property to the model and use the HasFilters Trait

```php
<?php

namespace App\Models;

use App\Models\Filters\CreatedAfterFilter;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    protected array $filters = [
        CreatedAfterFilter::class,
    ];
}
```

Now you can apply values to the filter and filter models out of the database using this filter.
The key for applying the filter is - if not changed - the filter class basename in snake case. 

To filter programmatically use the filter-scope

```php 
Post::filter(['created_after_filter' => '2023-01-01'])->get();
```

To filter by query string use the filterByQueryString scope

```
// this url: https://.../posts?created_after_filter=2023-01-01
Post::filterByQueryString()->get();
```
