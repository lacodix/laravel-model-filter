---
title: Use Search
weight: 7
---

Searching models is really straight forward. You can define the database fields that shall be searchable and just apply
a single string to the search scope to run the search.

## Activate searchability

Just add a $searchable Property the model that contains all searchable database fields and use the IsSearchable trait

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;

class Post extends Model
{
    use IsSearchable;

    protected array $searchable = [
        'title',
        'content',
    ];
    
    // Alternative solution with method:
    public function searchable(): array
    {
        return [
            'title',
            'content',
        ];
    }
}
```

## Search by code

To search in posts for a value in the given fields just call

```php
Post::search('test')->get();
```

This will find all Posts that contains the word test in title OR content.

### Search only in specific fields

```php
Post::search('test', 'title')->get();
Post::search('test', ['title', 'content'])->get();
```

### Treat wildcard characters literally

The existing `search()` scope keeps SQL wildcard support for backwards compatibility. If the search term comes from
an ordinary text input and `%`, `_` (or SQLite `GLOB` characters in case-sensitive modes) should be treated as normal
characters, use the opt-in literal scope:

```php
Post::searchLiteral('100%_complete')->get();
Post::searchLiteral('100%_complete', ['title', 'content'])->get();
```

`searchLiteral()` supports the same fields, relations, and search modes as `search()`. It also keeps all search values
in query bindings.

Applications that only expose ordinary text inputs can enable this behavior for every `search()` call, including
query-string and relation searches, without changing their calling code:

```php
// config/model-filter.php
'search_wildcards_as_literals' => true,
```

The option defaults to `false`, so upgrading the package does not change existing wildcard searches.

For historical compatibility, `search('0')` still skips the search because the original scope used Laravel's truthy
`when()` condition. The new `searchLiteral('0')` scope searches for the digit normally.

### Limit user-provided search input

Search scopes can create several predicates per field, especially with the `CONTAINS_ANY` and `CONTAINS_ALL` modes.
Applications that pass user input to a search scope can set character and term limits:

```php
// config/model-filter.php
'search_max_characters' => 320,
'search_max_terms' => 16,
'search_limit_exceeded_behavior' => 'empty',
```

Input above either limit deliberately produces no results. Both options default to `null`, so package upgrades remain
backwards compatible and **do not enable any protection by themselves**. Use positive integers to opt into limits.

Set `search_limit_exceeded_behavior` to `throw` when the caller must distinguish rejected input from a genuine empty
result:

```php
use Lacodix\LaravelModelFilter\Exceptions\SearchInputException;

try {
    $posts = Post::searchLiteral($search)->get();
} catch (SearchInputException) {
    // Convert this to an application-specific validation response.
}
```

The default behavior is `empty` for backwards compatibility. Any value other than `empty` or `throw` raises an
`InvalidArgumentException` when a configured limit is exceeded, so configuration mistakes do not fail silently.

## Search by a query string

```php
Post::searchByQueryString()->get();
```

and open the url

```
https://.../posts?search=test
```

You can change the name of query parameter with the config file value model-filter.search_query_value_name

### Search only in specific fields

```
https://.../posts?search=test&search_for=title
https://.../posts?search=test&search_for[]=title&search_for[]=content
```

## Search mode

Change the search mode by using an associative array for the $searchable property

```php
...

use Lacodix\LaravelModelFilter\Enums\SearchMode;
    
    ...
     
    protected array $searchable = [
        'title' => SearchMode::STARTS_WITH,
        'content' => SearchMode::ENDS_WITH
    ];
    
    ...
```

Available search modes are (the string behind is for usage in query string)
- SearchMode::EQUAL 'equal'
- SearchMode::LIKE (default) 'like'
- SearchMode::LIKE_CASE_SENSITIVE 'like_case'
- SearchMode::STARTS_WITH 'starts_with'
- SearchMode::STARTS_WITH_CASE_SENSITIVE 'starts_with_case'
- SearchMode::ENDS_WITH 'ends_with'
- SearchMode::ENDS_WITH_CASE_SENSITIVE 'ends_with_case'
- SearchMode::CONTAINS_ANY 'contains_any'
- SearchMode::CONTAINS_ANY_CASE_SENSITIVE 'contains_any_case'
- SearchMode::CONTAINS_ALL 'contains_all'
- SearchMode::CONTAINS_ALL_CASE_SENSITIVE 'contains_all_case'

The Contains modes behave identical to like modes, if only one search term is given. As soon as you search
for multiple terms, it behaves different. searching for "test name" will find only entries that contain 
"test name" with like modes, but it will find "test" and "name" in the contains modes. Please keep in mind
that the contains searches are very expensive in the database, since it splits up your search terms and will
perform multiple like comparisons.

Contains modes normalize whitespace before splitting, so spaces, tabs, and newlines all separate terms.

SQLite does not provide Unicode-aware `LIKE` case folding. The package therefore builds bound `GLOB` patterns with
single-character Unicode case variants. Full multi-character folds cannot be represented by a `GLOB` character class:
for example, `straße` matches `STRAẞE`, but `STRASSE` is not considered equivalent.

## More flexibility

the above search settings always have the same behaviour on the post model. If mode is set once the mode
is never changed. But it is also possible to change the mode by adding an additional parameter to the scope. 

```php
Post::search('test', [
    'title' => SearchMode::EQUAL,
    'content' => SearchMode::LIKE
])->get();
```

With this solution you can change built-in modes for the given searchable fields on calling the search.
Model-declared custom strategies remain authoritative and cannot be replaced by these overrides.
This flexibility is also possible via query-string

```
https://.../posts?search=test&search_for[title]=equal&search_for[content]=like
```

You can also change the name of query parameter search_for with the config file value 
model-filter.search_query_fields_name

### Security

For security reason it is only possible to override search mode of searchable fields. In the above
example the post model also needs the property $searchable. If a field isn't available in the 
$searchable list, it will never be searched, even if added in querystring.

```php 
protected array $searchable = [
    'title',
    'content',
];
```


## Custom search strategies

For application-specific predicates, implement `Lacodix\LaravelModelFilter\Contracts\SearchStrategy` and return
instances from the model's `searchable()` method. `SearchMode` implements the same interface, so existing field
lists, mode strings and enum declarations continue to work.

This illustrative strategy searches an application-maintained normalized column with exact equality. It can be
used for a root field or a related model's field; the package supplies the current builder and a qualified logical
field such as `posts.title` or `comments.title`.

```php
<?php

namespace App\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Lacodix\LaravelModelFilter\Contracts\SearchStrategy;

final class NormalizedExactSearch implements SearchStrategy
{
    public function __construct(private readonly string $column)
    {
    }

    public function applyQuery(Builder $query, string $field, string $search): Builder
    {
        // The destination column is trusted application configuration.
        $column = Str::beforeLast($field, '.').'.'.$this->column;

        return $query->where($column, mb_strtolower($search));
    }

    public function applyLiteralQuery(Builder $query, string $field, string $search): Builder
    {
        // Equality already treats wildcard characters literally.
        return $this->applyQuery($query, $field, $search);
    }
}
```

Declare the strategy on a model that uses `IsSearchable`:

```php
use App\Search\NormalizedExactSearch;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\SearchMode;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;

class Post extends Model
{
    use IsSearchable;

    public function searchable(): array
    {
        return [
            'title' => new NormalizedExactSearch('title_normalized'),
            'content' => SearchMode::LIKE,
        ];
    }
}

Post::search('EXAMPLE', ['title'])->get();
Post::searchLiteral('100%_complete', ['title'])->get();
```

The example requires an existing `title_normalized` column populated using the same normalization. For a relation,
register e.g. `'comments.title' => new NormalizedExactSearch('title_normalized')` on a model with a `comments()`
relation and a normalized column on comments. Dot-notated nested relations are supported too.

### Predicate and trust contract

- Both methods must append predicates to and return the supplied builder. Do not execute the query, replace the
  builder, clear existing constraints, remove scopes, or change unrelated query state. A strategy may add AND and
  internal OR conditions: the package wraps each custom field predicate and ORs it with the other searchable fields,
  preserving the surrounding search group, relation constraints and caller/global scopes.
  To represent no match, a trusted strategy must append an explicit false predicate such as `whereRaw('0 = 1')`, rather than return an unchanged builder.
- `$field` is the qualified logical field. A strategy owns any mapping to another approved column and preparation
  of the trimmed search value. Use query bindings for values; never interpolate input into SQL or identifiers.
- `applyLiteralQuery()` must treat wildcard characters as ordinary input. It is selected by `searchLiteral()` or
  `search_wildcards_as_literals`. The package does not escape or normalize input for a custom strategy. Empty input
  and configured input limits are checked before predicate methods run. Legacy `search('0')` still skips searching;
  `searchLiteral('0')` invokes the strategy.
- Strategies are trusted server code, not an authorization sandbox. Declare instances on the model; the package
  has no registry or class-name resolver. Never construct strategies or column mappings from request input. A caller
  can narrow fields but cannot replace a model-declared strategy with a mode or another instance. Passing a strategy
  for an ordinary field raises `InvalidArgumentException`; undeclared fields are ignored. Unknown mode strings retain
  the legacy LIKE fallback and are never instantiated as classes. Invalid non-string declarations fail clearly;
  strategy instances need an associative logical field key.
- `searchableFields()` returns a collection of field names to `SearchStrategy` values. Integrations may filter this
  collection and pass `->all()` back to `search()`: canonical model strategies are preserved even when `searchable()`
  creates fresh instances. Enum/string mode overrides continue to work on ordinary fields.

Indexing, storage, normalization rules, database portability, and any cryptographic/key lifecycle are application
responsibilities. The package provides no encryption, blind index or confidentiality guarantee. Field narrowing is
not authorization: keep authorization scopes on the query. As before, an empty or entirely unknown field selection
leaves the query unchanged.
