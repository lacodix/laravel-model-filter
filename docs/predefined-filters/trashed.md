---
title: Trashed Filter
weight: 1
---

Sometimes you have models that uses Laravels SoftDeletes trait. In this cases the default
behaviour is showing only the models that aren't deleted, but sometimes you want to show all
models including deleted or even only deleted models.

For this cases you can add the TrashedFilter to your model. The TrashedFilter is based on
the SelectFilter and offers two options "with trashed" and "only trashed".

## Usage

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Filters\TrashedFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    protected array $filters = [
        TrashedFilter::class,
    ];
}
```

To apply this filter use

```
https://.../posts?trashed_filter=with_trashed
```

or

```php
Post::filter(['trashed_filter' => 'only_trashed'])->get()
```

The latter might be useless, since you could easily use ->onlyTrashed() directly. The benefit
comes with the out of the box and ready to use select box view.

## Visualisation and Translation

If you want configure the select options just publish views and/or language files and change the
translations of the package.