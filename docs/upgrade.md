---
title: Upgrade guide
weight: 4
---

## from v4.6 to v4.7

Version 4.7 keeps all existing public method signatures and adds literal-search functionality. It is a minor release
because it also fixes search behavior that can change result sets.

### Filter input structure hardening

Built-in filters now reject malformed query-parameter structures through their configured validation mode. In
`ValidationMode::FILTER` the filter is skipped; in `ValidationMode::THROW` malformed structures now produce a Laravel
`ValidationException`, including for direct `populate(...)->apply(...)` calls where PHP warnings or query errors could
previously occur.

Existing custom filters that override `apply()` keep their previous behavior for backward compatibility and therefore
do not receive the direct-application input guard. Move only their query logic to `applyFilter()` to opt into it; model
scopes continue to run their normal validation either way.

### Security fix for SQLite case-sensitive search

All five `*_CASE_SENSITIVE` modes now bind SQLite `GLOB` values instead of interpolating search input into raw SQL.
This closes an SQL-injection vulnerability and is active for both `search()` and `searchLiteral()`; no opt-in is
required.

### Published configuration

Applications with an already published `config/model-filter.php` should add these keys:

```php
'search_wildcards_as_literals' => false,
'search_max_characters' => null,
'search_max_terms' => null,
'search_limit_exceeded_behavior' => 'empty',
```

The first option can opt existing `search()` and `searchByQueryString()` calls into literal wildcard handling. The two
limits are disabled by default, so upgrading alone does not limit abusive input. Positive integers enable them. The
default `empty` behavior preserves the existing query contract; use `throw` to receive a
`Lacodix\LaravelModelFilter\Exceptions\SearchInputException` instead. Any other behavior value raises an
`InvalidArgumentException` when a configured limit is exceeded.

### Search-result changes

- MySQL and PostgreSQL case-insensitive modes now use multibyte lowercase normalization, so uppercase Unicode input
  such as `MÜLLER` can find `Müller`.
- SQLite case-insensitive modes use bound `GLOB` patterns with Unicode character variants instead of ASCII-only
  `LIKE`, so some Unicode case variants now produce additional matches. Multi-character folds remain unsupported:
  `straße` can match `STRAẞE`, but not `STRASSE`.
- `CONTAINS_ANY` and `CONTAINS_ALL` now split on normalized whitespace. Tabs and newlines therefore separate terms in
  addition to ordinary spaces.

The historical `search('0')` behavior is unchanged and still skips the search. The new `searchLiteral('0')` scope
searches for `0` normally.

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
