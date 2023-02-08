---
title: Filter Groups
weight: 3
---

Sometimes it is necessary to group your filters. Think about situations where you want to
use different filters for frontend and backend. Maybe there are hidden fields on your models,
that are only visible to admins/users, but not visible for guests.

Your users shall be able to filter only a part of your filters or even more usefull you want
to use different views for your filters in front and backend. In the backend you could have kind
of a resource-table while in the frontend u use styled cards to visualize your models.

You can use different filter groups by adding a multidimensional array to the filters property

## Creating Groups

```php
<?php

namespace App\Models;

use App\Models\Filters\PublishedFilter;
use App\Models\Filters\CreatedAtFilter;
use App\Models\Filters\HotFilter;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    protected array $filters = [
        'frontend' => [
            HotFilter::class,
        ],
        'backend' => [
            CreatedAtFilter::class,
            PublishedFilter::class,
        ],
    ];
}
```

## Filter for groups

To make use of the different filter groups just add a group parameter to the scope

```php
Post::filterByQueryString('frontend')->get()
```
or
```php 
Post::filter(['hot_filter' => 'hot'], 'frontend')->get();
Post::filter(['created_at_filter' => '2023-01-01'], 'backend')->get();
```

If you don't add a group, the group name will be '__default'. So if you want to use groups
only in several cases just create a default group like this:

```php
    protected array $filters = [
        'frontend' => [
            HotFilter::class,
        ],
        '__default' => [
            CreatedAtFilter::class,
            PublishedFilter::class,
        ],
    ];
```

With this option you just can omit the group name on scopes to use the default group. 

## Visualisation

If you use different groups for Backend and Frontend you can still use the filter overview
component and add a group to it.

```html
    <x-lacodix-filter::model-filters :model="Post::class" group="backend" />
```

