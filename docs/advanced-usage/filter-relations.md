---
title: Filter for Relations
weight: 4
---

In the simple cases, you can use the [RunsOnRelation trait](../extended-filters/runs-on-relation.md) to run any existing filter on a relation.

However, if you have more complex requirements, like nested relations, you can manually use Laravel's `whereHas` method or use the `RunsOnRelation` trait with a dot-notated relation name.

## Basic Relation Filter

For most cases, just using the trait is enough.

```php
class PostTitleFilter extends StringFilter
{
    use RunsOnRelation;

    protected string $relation = 'post';
    protected string $field = 'title';
}
```

## Nested Relations

You can also filter through nested relations by using a dot-notated relation name:

```php
class AuthorPostTitleFilter extends StringFilter
{
    use RunsOnRelation;

    protected string $relation = 'author.posts';
    protected string $field = 'title';
}
```

This will automatically wrap the logic in `whereHas('author.posts', ...)` and correctly qualify the `title` column using the `posts` table name.

## Complex Custom Relation Filters

If you need more control, such as adding additional constraints to the `whereHas` query, you can manually override the `applyFilter` method while still using the trait:

```php
class CustomRelationFilter extends StringFilter
{
    use RunsOnRelation;

    protected string $relation = 'posts';
    protected string $field = 'title';

    public function applyFilter(Builder $query): Builder
    {
        // Add custom constraints inside the whereHas closure
        $query->where('published', true);

        // Call parent logic to apply the string filter on the title field
        return parent::applyFilter($query);
    }
}
```
