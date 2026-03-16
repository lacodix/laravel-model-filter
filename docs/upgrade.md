---
title: Upgrade guide
weight: 4
---

## from v3 to v4

With v4 the `BooleanFilter` was renamed to `OptionFilter`. This was done to clarify its purpose
and to make room for a new `BooleanFilter` that handles single boolean database fields.

### Renaming BooleanFilter to OptionFilter

The old `BooleanFilter` which allowed multiple checkbox selections in one filter class is now
named `OptionFilter`.

To migrate your existing boolean filters, just rename the extended class and the import:

```php
// old
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
class MyFilter extends BooleanFilter { ... }

// new
use Lacodix\LaravelModelFilter\Filters\OptionFilter;
class MyFilter extends OptionFilter { ... }
```

Also if you used the `$component` property in your filter classes, it should be changed from `boolean` to `option`.

### Accessing filter values

In v4 we introduced a new way to access filter values in your `apply` method. Instead of accessing `$this->values[$this->queryName()]` directly, you should now use the `getValue()` method.

This is especially useful if you want to create custom filters that extend `SingleFieldFilter`.

This is a breaking change if you use Filters where queryName and field are not the same.

```php
// old
public function apply(Builder $query): Builder
{
    return $query->where('field', $this->values[$this->queryName()]);
    # or even
    return $query->where('field', $this->values[$this->field]);
}

// new
public function apply(Builder $query): Builder
{
    return $query->where('field', $this->getValue());
}
```

If you need all values (e.g. in `OptionFilter`), you can use `getValues()`.

```php
// old
foreach ($this->options() as $key) {
    $query->when(
        ! is_null($this->values[$key] ?? null),
        fn ($query) => $query->where($key, $this->values[$key])
    );
}

// new
foreach ($this->options() as $key) {
    $query->when(
        ! is_null($this->getValue($key)),
        fn ($query) => $query->where($key, $this->getValue($key))
    );
}
```

### New BooleanFilter

The new `BooleanFilter` is now a single field filter. It is used to filter for a single boolean
database field.

```php
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;

class PublishedFilter extends BooleanFilter
{
    protected string $field = 'published';
}
```

### Signature of populate method

The `populate` method in the base `Filter` class and all its subclasses has been updated to accept `null` as a value. 
If you have custom filter classes that override the `populate` method, you must update the method signature.

```php
// old
public function populate(string|array $values): static

// new
public function populate(string|array|null $values): static
```

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
