---
title: Use Sorting
weight: 8
---

Sorting models is also straight forward like searching. You can define the database fields that shall be usable for
sorting and just apply the sorting fields to the scope.

## Activate sortablility

Just add a $sortable Property the model that contains all sortable database fields and use the IsSortable trait

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\IsSortable;

class Post extends Model
{
    use IsSortable;

    protected array $sortable = [
        'title',
        'created_at',
        'content',
    ];
}
```

## Sort by code

To sort posts by a given field just call

```php
Post::sort(['title' => 'desc'])->get();
```

The above example is really useless since you could simply run Posts::orderBy('title', 'desc')
but keep in mind you can simply create an array with multiple sorted fields and different
directions. Internally it is checking if the given fields are allowed to sort, and only
applies the sorting to allowed fields.

This also allows to use the sort by query string.

## Search by a query string

```php
Post::searchByQueryString()->get();
```

and open the url

```
https://.../posts?sort[title]=desc
```

You can change the name of query parameter with the config file value model-filter.sort_query_value_name

## Search direction

You can use the both directions `asc` and `desc`. The usage is case insensitive.

### Security

For security reason it is only possible to sort for fields that are marked as sortable. 
If a field isn't available in the $sortable list, it will never be sorted by this field, 
even if added in querystring.

```php 
protected array $sortable = [
    'title',
    'content',
];
```

The following sort will not be executed for this case

```
https://.../posts?sort[created_at]=desc
```
