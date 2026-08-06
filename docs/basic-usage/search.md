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

With this solution you can change the mode for the given searchable fields on calling the search.
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
