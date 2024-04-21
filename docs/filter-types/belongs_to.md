---
title: Belongs To Filter
weight: 10
---

A BelongsToFilter is a special version of the select filter. To get more details please see the select filter docs.
The BelongsToFilter populates the options automatically by running a query on the database. For this it needs
the eloquent model of the relation in addition to the field name.

## Create the filter

```bash
php artisan make:filter TestBelongsToFilter -t belongs-to -f user_id --relation="\App\Models\User" --title=username
```

this creates a filter class that extends BelongsToFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\BelongsToFilter;

class TestBelongsToFilter extends BelongsToFilter
{
    protected string $field = 'user_id';

    protected string $relationModel = \App\Models\User::class;
    protected string $titleColumn = 'username';
}

```

This will prepopulate a select filter with all the users in your users table, represended by the colum username.
You can additionaly configure the id-table, if you use another column as key.
```php
    protected string $relationModel = \App\Models\User::class;
    protected string $idColumn = 'key';
    protected string $titleColumn = 'username';
```

## Multiselect

Like the base select filter you can enable the multiselect mode if you want to select by multiple users
```php
public FilterMode $mode = FilterMode::CONTAINS;
```

## Mapping the titles

In some situations you don't need the raw title value from the database, but want to map it for example through a
translation. For that cases you can add a `mapTitle` method to your filter

```php
    public function mapTitle(string $title) {
        return __('titles.' . $title);
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
