---
title: Create and apply filters
weight: 1
---

Filters are class based. This means each filter is located in its own class file and will
be located in \App\Models\Filters. Filters can be applied to multiple models.
Filters can use one of the existing base filters or can be set up individually.

For filter creation we added an artisan comman
```
php artisan make:filter
```
You can call this command without any parameters to get prompted for your settings. You get asked which
kind of filter you want to create and finally will get asked for needed parameters and options. In our
examples we will use parameters and options to create the filter in one shot.

## Create your first filter

```bash
php artisan make:filter CreatedAfterFilter -t date -f created_at
```

This filter will be created as the class CreatedAfterFilter.php in the folder app/Models/Filters.
It is a filter of type 'date' and will be bound to database field 'created_at'. It can be used in
all Models, that have a created_at datetime field.

All filters have a mode for filtering, that can change the behaviour of the filter.
The default mode of almost all filters is filtering for "equal" values. The default mode
for a string filter is "like". Since CreatedAfterFilter shall find all models that are
created AFTER a given date, the mode must be changed.

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

## Add filter to model

To make this filter usable in a model just add a $filters property to the model and use the 
HasFilters Trait

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
    
    // Alternative solution with method:
    public function filters(): array
    {
        return [
            CreatedAfterFilter::class,
        ];
    }
}
```

## Use the filter

Now you can apply values to the filter and filter models from the database using this filter.
The key for applying the filter defaults to the filters class basename in snake case.<br />
CreatedAfterFilter => created_after_filter

To filter programmatically use the filter-scope

```php 
Post::filter(['created_after_filter' => '2023-01-01'])->get();
```

## Filter by query string

To filter by query string use the filterByQueryString scope.

```php
Post::filterByQueryString()->get();
```

and call the corresponding url like this

```
https://.../posts?created_after_filter=2023-01-01
```

## Multiple Filters

Filtering for multiple values is always an and-condition. If a filter value
doesn't matter it must be omitted.

```php 
Post::filter([
    'created_after_filter' => '2023-01-01',
    'published_filter' => true,
])->get();
```

Or via query string

```
https://.../posts?created_after_filter=2023-01-01&published_filter=1
```

With the same code as for one filter

```php
Post::filterByQueryString()->get();
```
