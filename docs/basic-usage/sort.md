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

## Sort by a query string

```php
Post::sortByQueryString()->get();
```

and open the url

```
https://.../posts?sort[title]=desc
```

You can change the name of query parameter with the config file value model-filter.sort_query_value_name

## Sort direction

You can use the both directions `asc` and `desc`. The usage is case insensitive.

You can also omit the direction if you want `asc` sorting

```php
Post::sort(['title'])->get();
```

## Default sorting

Sometimes you need the possibility to sort by a column even if no sorting direction is given. Or you want to
sort by another column even if some sortings are given. For such cases you can define a default sorting direction
on your sortable columns:

```php
    protected array $sortable = [
        'title',
        'created_at' => 'desc',
        'content',
    ];
```

In this example the columns will be sorted in descending order by create_at column even if it is not given.

```php
Post::sort()->get();
...
Post::sortByQueryString()->get();
```

This is indeed more useful for usage with querystring, since you could just add the direction on your own by code.

ATTENTION: keep in mind that it is still necessary to call one of the scopes `sort` or `sortByQueryString` to apply
the sorting functionality. If you want to sort your models even without calling one of this functions, take in account
to use a default global scope on your model.

ATTENTION: kepp also in mind that given sort-parameters will automatically overwrite default sorting.

```php
    protected array $sortable = [
        'title',
        'created_at' => 'desc',
        'content',
    ];
```

```php
    Post::sort(['title' => 'desc'])->get();
```

in this example it will NOT sort by created_at but only by title.
Default sorting will only be applied if not manual sorting is given.

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
