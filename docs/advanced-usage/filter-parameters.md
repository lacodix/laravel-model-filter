---
title: Filter Parameters
weight: 3
---

When adding filters to models you usually use the $filters property

```php
    protected array $filters = [
        CreatedAfterFilter::class,
    ];
```

With this solution you are not able to set properties of the filter. But sometimes you need multiple almost
identical filters, or you just want to use different headlines for the same filter when using it in different
models. It would be kind of waste to create multiple filter classes for that.

As an alternative you can just use the filters() method of the HasFilters trait.

```php
<?php

namespace App\Models;

use App\Models\Filters\CreatedAfterFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    public function filters(): array
    {
        return [
            new CreatedAfterFilter()
        ];
    }
}
```

With that solution you can instantiate the filter and use methods on it.
For convenience there are already some methods available on all filter classes.

```php
    public function filters(): array
    {
        return [
            (new CreatedAfterFilter())
                ->setQueryName('my_query_string')
                ->setMode(FilterMode::LOWER)
                ->setValidationMode(ValidationMode::THROW)
                ->setTitle(__('My Headline'))
                ->setComponent('date'),
        ];
    }
```

All this methods should be self explaining. In your own filter classes you can create more methods
like this and you just need to return itself.

## Use Base Filters Immediate

With this option you can just use some available base filters without creating your
own classes. For the date, string and boolean-filter you don't need to create dedicated
filter classes. 

Filter classes have a huge benefit if you can reuse it like an reusable 
created_at filter, it is only created once and can be applied to multiple models.


```php
    public function filters(): array
    {
        return [
            (new DateFilter('created_at'))
                ->setTitle('Created between')
                ->setQueryName('created_at_between')
                ->setMode(FilterMode::BETWEEN),

            (new StringFilter('title'))
                ->setTitle('Title')
                ->setQueryName('title_starts_with')
                ->setMode(FilterMode::STARTS_WITH),

            (new NumericFilter('counter'))
                ->setTitle('Count max')
                ->setQueryName('counter_lower_filter')
                ->setMode(FilterMode::LOWER_OR_EQUAL),
        ];
    }
```

To apply this filters the keys are equal to the query names.

```php 
Post::filter([
    'created_at_between' => ['2023-01-01', '2023-01-31'],
    'title_starts_with' => 'test',
    'counter_lower_filter' => 500,
])->get();
```

or open the url

```
https://.../posts?created_at_between[]=2023-01-01&created_at_between[]=2023-01-31&title_starts_with=test&counter_lower_filter=500
```
