---
title: RunsOnRelation Trait
weight: 3
---

The `RunsOnRelation` trait allows you to apply any filter logic to an Eloquent relation instead of the main model.
This is particularly useful when you want to filter a model based on the attributes of its related models.

## Usage

To use the trait, simply include it in your filter class and specify the `$relation` property.

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Traits\RunsOnRelation;

class CommentAuthorNameFilter extends StringFilter
{
    use RunsOnRelation;

    protected string $relation = 'author';
    protected string $field = 'name';
}
```

In this example, the filter will search for comments where the related `author` has a `name` matching the filter value.
Under the hood, this uses Laravel's `whereHas` method.

The trait respects the `$mode` of your filter (e.g., `EQUAL`, `LIKE`, `STARTS_WITH`, etc.), just like a normal filter.

```php
$query->whereHas('author', function ($query) {
    $query->where('authors.name', 'LIKE', '%...%');
});
```

The trait automatically handles column qualification, ensuring that the filter uses the table name of the related model.

## Custom Filters

If you are using a custom filter and want to run it on a relation, you can still use the trait.
The trait works by overriding the `apply()` method and calling a new `applyFilter()` hook.

If your custom filter currently overrides `apply()`, you should rename it to `applyFilter()` to make it compatible with the `RunsOnRelation` trait.

```php
use Lacodix\LaravelModelFilter\Filters\Filter;
use Lacodix\LaravelModelFilter\Traits\RunsOnRelation;
use Illuminate\Database\Eloquent\Builder;

class MyCustomFilter extends Filter
{
    use RunsOnRelation;

    protected string $relation = 'someRelation';

    public function applyFilter(Builder $query): Builder
    {
        // Your custom filtering logic here
        return $query->where('some_related_column', $this->getValue());
    }
}
```

## Manual Usage

If you need more control or want to apply a relation filter only in specific parts of your logic, you can use the `runOnRelation()` helper method provided by the trait:

```php
public function apply(Builder $query): Builder
{
    // Do some main query stuff
    return $this->runOnRelation($query, function ($q) {
        // This closure runs inside a whereHas context
        return $q->where('related_col', 'some_val');
    });
}
```
