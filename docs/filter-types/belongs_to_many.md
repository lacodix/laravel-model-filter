---
title: Belongs To Many Filter
weight: 11
---

A BelongsToManyFilter is a special version of the select filter like the BelongsToFilter. To get more details please 
see the select filter and belongs to filter docs.
The BelongsToManyFilter populates the options automatically by running a query on the database. For this it needs
the eloquent model of the relation in addition to the field name.

The field name is used for the relation on the base model.

## Create the filter

```bash
php artisan make:filter TestBelongsToManyFilter -t belongs-to-many -f tags --relation="\App\Models\Tag" --title=title
```

this creates a filter class that extends BelongsToManyFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\BelongsToManyFilter;

class TestBelongsToManyFilter extends BelongsToManyFilter
{
    protected string $field = 'tags';

    protected string $relationModel = \App\Models\Tag::class;
    protected string $titleColumn = 'title';
}

```

This will prepopulate a select filter with all the tags in your tags table, represended by the colum title.
You can additionaly configure the id-table, if you use another column as key.
```php
    protected string $relationModel = \App\Models\Tag::class;
    protected string $idColumn = 'key';
    protected string $titleColumn = 'username';
```

Your Model that shall be filtered by tags must contain a BelongsToMany-Relation named tags, represended by the
`$fields` table.

## Multiselect

Like the base select filter you can enable the multiselect mode if you want to select by multiple tags
```php
public FilterMode $mode = FilterMode::CONTAINS;
```

## Mapping the titles

In some situations you don't need the raw title value from the database, but want to map it for example through a
translation. For that cases you can add a `mapTitle` method to your filter

```php
    public function mapTitle(string $title) {
        return __('tags.' . $title);
    }
```

## Changing the Query

Sometimes you need to manipulate the base query for receiving the values from the database. For such
situations you can overwrite the method `relationQuery`

```php
    public function relationQuery(): Builder {
        return parent::relationQuery()
            ->where('tenant_id', Tenant::current()->id);
    }
```

## Filter Modes

- FilterMode::EQUAL
- FilterMode::CONTAINS
