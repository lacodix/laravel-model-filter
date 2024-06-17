---
title: Upgrade guide
weight: 4
---

## from v2 to v3

### (Breaking) Introduction of filters(), searchable() and sortable() methods.

Comparable with introduction of casts method in Laravel 11, we introduced the filters(), searchable() and sortable() 
methods on the traits. You can now use the properties or the methods to declare your filters, searchable and sortable
settings

Current version with properties (and still valid):
```php 
    protected array $searchable = [
        'name',
    ];

    protected array $sortable = [
        'name',
    ];

    protected array $filters = [
        CompanyFilter::class,
    ];
```

New option with methods:

```php 
    public function searchable(): array
    {
        return [
            'name',
        ];
    }

    public function sortable(): array
    {
        return [
            'name',
        ];
    }
    
    public function filters(): array
    {
        return [
            CompanyFilter::class,
        ];
    }
```

Especially with filters this gives you much more flexibility, because you are able to return an instantiated 
object of a filter, and with this flexibility you can use the same filter class for different filter behaviours.
Please see our filter tests for examples.

Unfortunately filters and searchable methods have already been there and might be overwritten in your code, like
we did it in our test cases. If you just followed the instructions it doesn't break your code. If so, please 
replace your calls in the following way:

- filters() -> filterInstances()
- searchable() -> searchableFields()

### Search can be set to case sensitive or insensitive

The behaviour until v2 was not deterministic. Since we always used the LIKE operator there was a different 
result in different databases. Postgres is always working case sensitive with the LIKE operator, while SQLite
is always insensitive, and with MySql it depends, while it is usually also insensitive.

All search modes of v2 (except EQUAL) are now case insensitive, since this is the most expected behaviour.
But we added more modes to give you the option to search case insensitive and even with gaps between. 

If you use Postgres and want keep current case sensitive searching, just set all LIKE search modes to 
LIKE_CASE_SENSITIVE

```php
// If no searchmode is given, LIKE is the default
public function searchable(): array
{
    return [
        'name',
    ];
}

// Replace it:
public function searchable(): array
{
    return [
        'name' => SearchMode::LIKE_CASE_SENSITIVE,
    ];
}
```
