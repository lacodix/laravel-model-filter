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

## Search by a query string

```php
Post::searchByQueryString()->get();
```

and open the url

```
https://.../posts?search=test
```

You can change the name of query parameter with the config file value model-filter.search_query_value_name

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
