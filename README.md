# laravel-model-filter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lacodix/laravel-model-filter.svg?style=flat-square)](https://packagist.org/packages/lacodix/laravel-model-filter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/lacodix/laravel-model-filter/test.yaml?branch=master&label=tests&style=flat-square)](https://github.com/lacodix/laravel-model-filter/actions?query=workflow%3Atest+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/lacodix/laravel-model-filter/style.yaml?branch=master&label=code%20style&style=flat-square)](https://github.com/lacodix/laravel-model-filter/actions?query=workflow%3Astyle+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/lacodix/laravel-model-filter.svg?style=flat-square)](https://packagist.org/packages/lacodix/laravel-model-filter)

A lightweight laravel package to filter and search models on database with ease. The creation and usage of filters
is inspired by laravel/nova (https://nova.laravel.com/)

## Installation

You can install the package via composer:

```bash
composer require lacodix/laravel-model-filter
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-model-filter-config"
```

This is the contents of the published config file:

```php
return [
    'date_format' => 'Y-m-d',
    'search_query_value_name' => 'search',
    'search_query_fields_name' => 'search_for',
];
```

## Usage Example Of Filters

Create your first filter

```bash
php artisan make:filter CreatedAtFilter -t date -f created_at
```

This filter will be created as a Class CreatedAtFilter.php in the folder app/Models/Filters. It can be used in 
all Models, that have a created_at datetime field.

To apply this filter to any model just add a $filters property to the model and use the HasFilters Trait

```php
<?php

namespace App\Models;

use App\Models\Filters\CreatedAtFilter;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    protected array $filters = [
        CreatedAtFilter::class,
    ];
}
```

### Filter by query string

After the model now has filters, it can be automatically be filtered by query string.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        return Post::filterByQueryString()->get();
    }
}
```

To make use of this filter just call the corresponding route with query parameter

```
https://.../posts?created_at_filter=2023-01-01
```

The name of the query parameter equals the filter class name in snake case.
Nevertheless you can specify the query parameter name by adding the parameter $queryName to your filter classes

```php
    ...
    protected string $queryName = 'my_query_param'
```

then you can apply your filter by calling this url:

```
https://.../posts?my_query_param=2023-01-01
```

This filter can be applied to multiple models.

### Filter by code

To use the filter programmatically, you can just provide the parameters as array

```php
Post::filter(['created_at_filter' => '2023-01-01'])->get();
```

if you changed the query parameter name of your filter, you must take this also in account when using filter 
programmatically

```php
Post::filter(['my_query_param' => '2023-01-01'])->get();
```

### Multiple Filters

Filtering for multiple values is always an and-condition. If a filter value
doesn't matter it must be omitted.

### Change filter mode

All filters have a mode for filtering, that can change the behaviour of the filter.
The default mode of almost all filters is filtering for "equal" values. The default mode 
for a string filter is "like".

To change our created_at filter to filter for all posts that are created before
2023-01-01 including the first January, the LOWER_OR_EQUAL-mode can be used.

Insert the mode propety to the filter:
```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

class CreatedAtFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::LOWER_OR_EQUAL;

    protected string $field = 'created_at';
}
```

For all modes see section Filter modes

## Usage Example For Search

Just tell the model that shall be searchable what fields can be searched and use the IsSearchable trait

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
}
```

To search in posts for a value in the given fields just call

```php
Post::search('test')->get();
```

This will find all Posts that contains the word test in title OR content.

### Search by aquery string

```php
Post::searchByQueryString()->get();
```

and open url

```
https://.../posts?search=test
```

you can change the name of query parameter with the config file value model-filter.search

### Search mode

Change the search mode by using an associative array for the searchables:

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

Available search modes are
- SearchMode::STARTS_WITH;
- SearchMode::ENDS_WITH;
- SearchMode::EQUAL;
- SearchMode::LIKE (default);

### More flexibility

the above search settings always have the same behaviour on the post model. If mode is set once the mode is never changed.
But it is also possible to change the mode via parameter

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

### Security

For security reason it is only possible to override search mode of searchable fields. In the above
example the post model also needs the property $searchable

```php 
protected array $searchable = [
    'title',
    'content',
];
```

if you try to search for fields that are not present in $searchable, the search will not be applied.


## Filter types

### Date Filter

Create the filter
```bash
php artisan make:filter TestDateFilter -t date -f fieldname
```

this creates a filter class the extends DateFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\DateFilter;

class TestDateFilter extends DateFilter
{
    protected string $field = 'fieldname';
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname equals the given value.

Default mode is EQUAL

Change filter mode:

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

class TestDateFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::LOWER;

    protected string $field = 'fieldname';
}
```

Allowed modes are
- FilterMode::LOWER
- FilterMode::GREATER
- FilterMode::LOWER_OR_EQUAL
- FilterMode::GREATER_OR_EQUAL
- FilterMode::EQUAL (default)
- FilterMode::BETWEEN
- FilterMode::BETWEEN_EXCLUSIVE
- FilterMode::NOT_BETWEEN
- FilterMode::NOT_BETWEEN_INCLUSIVE

For using between and not between filters you have to provide two values to the filter. Ordering
of this values doesn't matter, the filter will detect if the first or second is the smaller one.

For providing multiple values use the following url 

```
https://.../posts?test_date_filter[]=2023-01-01&test_date_filter[]=2023-01-10
```

or programmatically

```php
Post::filter(['created_at_filter' => ['2023-01-01', '2023-01-10']])->get();
```

This will find all posts created between or not between 1st and 10th of January.
FilterMode::BETWEEN will include both days, FilterMode::BETWEEN_EXCLUSIVE will exclude both days.
FilterMode::NOT_BETWEEN will also exclude posts, that are created on one of both days,
FilterMode::NOT_BETWEEN_INCLUSIVE will include posts that are created on these days.

### String Filter

Create the filter
```bash
php artisan make:filter TestStringFilter -t string -f fieldname
```

this creates a filter class the extends StringFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\StringFilter;

class TestStringFilter extends StringFilter
{
    protected string $field = 'fieldname';
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname contains the given value.

Default mode is LIKE

Change filter mode:

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

class TestDateFilter extends DateFilter
{
    public FilterMode $mode = FilterMode::STARTS_WITH;

    protected string $field = 'fieldname';
}
```

Allowed modes are
- FilterMode::STARTS_WITH;
- FilterMode::ENDS_WITH;
- FilterMode::EQUAL;
- FilterMode::LIKE (default);

### Select Filter

Create the filter
```bash
php artisan make:filter TestSelectFilter -t select -f fieldname
```

this creates a filter class the extends SelectFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\SelectFilter;

class TestSelectFilter extends SelectFilter
{
    protected string $field = 'fieldname';

    public function options(): array
    {
        // add the allowed values here
        'value1',
        'value2',
        ...
    }
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname equals the given value.

The options function must be implemented, given values for the filter will be
removed if not contained in this array.

Select filters only have the EQUAL mode.

### Numeric Filter

Create the filter
```bash
php artisan make:filter TestNumericFilter -t numeric -f fieldname
```

this creates a filter class the extends NumericFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\NumericFilter;

class TestNumericFilter extends NumericFilter
{
    protected string $field = 'fieldname';
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname equals the given value.

Default mode is EQUAL

Change filter mode:

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;

class TestNumericFilter extends NumericFilter
{
    public FilterMode $mode = FilterMode::LOWER;

    protected string $field = 'fieldname';
}
```

Allowed modes are
- FilterMode::LOWER
- FilterMode::GREATER
- FilterMode::LOWER_OR_EQUAL
- FilterMode::GREATER_OR_EQUAL
- FilterMode::EQUAL (default)
- FilterMode::BETWEEN
- FilterMode::BETWEEN_EXCLUSIVE
- FilterMode::NOT_BETWEEN
- FilterMode::NOT_BETWEEN_INCLUSIVE

For using between and not between filters you have to provide two values to the filter. Ordering
of this values doesn't matter, the filter will detect if the first or second is the smaller one.

For providing multiple values use the following url

```
https://.../posts?test_numeric_filter[]=100&test_date_filter[]=1000
```

or programmatically

```php
Post::filter(['test_numeric_filter' => [100, 1000]])->get();
```

This will find all posts where fieldname is between 100 and 1000.
FilterMode::BETWEEN will include both values, FilterMode::BETWEEN_EXCLUSIVE will exclude both values.
FilterMode::NOT_BETWEEN will also exclude posts, that have one of the both values in the selected field,
FilterMode::NOT_BETWEEN_INCLUSIVE will include posts that have these values.


### Boolean Filter

Create the filter

```bash
php artisan make:filter TestBooleanFilter -t boolean
```

this creates a filter class the extends BooleanFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\BooleanFilter;

class TestBooleanFilter extends BooleanFilter
{
    public function options(): array
    {
        return [
            // add all boolean database columns here to filter for 
            'published',
            'active',
            'boolvalue',
        ];
    }
}
```

The boolean filter is the only base filter that can filter for multiple values, since 
it doesn't make sense to add multiple filter classes for multiple checkboxes. 
Nevertheless you are allowed to only add one single field to the options array,
if you want to filter only for one boolean value with this filter.

To use a boolean filter add it to the model like all other filters and
call it with a multidimensional value array:

```php
$filterValues = [
    'test_boolean_filter' => [
        'published' => true,
        'active' => false,
        'boolvalue' => true,
    ]
];

Post::filter($filterValues)->get();
```
This will filter all Posts that are published, but inactive, and have boolvalue true.

Using a boolean filter with querystring you can call an url like this:

```
https://.../posts?test_boolean_filter[published]=1&test_boolean_filter[active]=0
```

The given value must be castable to boolean - add the string "false" would result in true!!

Values that are not of interest can be omited like in this example "boolvalue" .

### Individual Filter

Create the filter
```bash
php artisan make:filter TestIndividualFilter
```

this creates a filter class the extends the base Filter class

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\Filter;

class TestIndividualFilter extends Filter
{
    public function apply(Builder $query, array|string $values): Builder
    {
        $value = is_array($values) ? current($values) : $values;

        return $query->where('field', $values);
    }
}
```

You have just to implement the apply function that can be created absolutely free. 
All given values will be added to the values parameter. To get a single value it can 
be used like in select, string and date-filter

```
https://.../posts?test_individual_filter=myvalue
```

or 

```php
Post::filter(['test_individual_filter' => 'myvalue'])->get()
```

Both examples will result in a string-values parameter on the apply function.

To get an array with multiple values follow the boolean-filter example.

## Filter Validation

Some filters include validation. DateFilters for example will validate the inserted values for
the given format 'Y-m-d'. The used format can be overwritten with the config value 'date_format'.
If a given value does not fit the validation, an ValidationException will be thrown.

NumericFilters validates the input for numerical values.

SelectFilter validate against the available options.

### Validation Mode

Usually invalid values are just ignored. If invalid values are set, the filter is just not applied.
You can switch the validation mode to a hard validation, to throw a ValidationException with invalid data.
Just set the Validation Mode of your Filter like in the following example.

```php
    ...
    public ValidationMode $validationMode = ValidationMode::THROW;
    ...
```

### Own Validatoin rules

You can add your own validation rules to all filters by overwriting the rules() function. You can
use all possibilities of Laravels Validator.

```php
    public function rules(): array
    {
        return [
            'fieldname' => 'in:' . implode(',', $this->options()),
        ];
    }
```

depending on the ValidationMode of your filter, the filter will not be applied (default) or throw an exception.

### Customize Error Messages & Attributes

If you wish to customize the validation messages that are thrown with the ValidationException,
you can overwrite the $messages property of the Filter.

If you want to keep the default Laravel validation messages, but just customize the :attribute 
portion of the message, you can specify custom attribute names using the $validationAttributes
property.

```php 
    protected $messages = [
        'fieldname.numeric' => 'The Field must be a number.',
    ];
```

and/or    

```php
    protected $validationAttributes = [
        'fieldname' => 'Field'
    ];
```

if you don't know the fieldname before, you can also overwrite the corresponding methods
messages() and validationAttributes()

```php 
    protected function messages() {
        return [
            $this->field . '.numeric' => 'The Field must be a number.',
        ];
    }
```

and/or

```php
    protected function validationAttributes() {
        return [
            $this->field => 'Field',
        ];
    }
```

## Advanced Usage

### Tweak filter behaviour

Sometimes you don't need a direct match between filter values and database. For
example if you want a SelectFilter for Verified and Unverified users, but the
database doesn't contain a bool or string-value but kind of an email_verified_at
date field that implicit means a user is verified if this field is not null.

You can extend the available filters with own query logic. First create a SelectFilter
as usual.

```bash 
php artisan make:filter UserVerified -t select -f email_verified_at
```

Then add option values like usual and additionally extend the filters query function 

```php  
<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;

class UserVerifiedFilter extends SelectFilter
{
    protected string $field = 'email_verified_at';

    public function options(): array
    {
        return [
            'verified',
            'unverified',
        ];
    }

    protected function query(Builder $query, array $values): Builder
    {
        return match($values[$this->field]) {
            'verified' => $query->whereNotNull($this->field),
            'unverified' => $query->whereNull($this->field),
            default => $query,
        };
    }
}
```

### Use base filters without creating dedicated filter classes

For the date, string and boolean-filter you don't need to create dedicated
filter classes. Filter classes have a huge benefit if you can reuse it like the
created_at filter in the first example, it is only created once and can be 
applied to multiple models with the $filter-property.

If a date, string and boolean filter is only used on one single model, and you
don't want to create separate classes for this filter, there is another way to
create filters by using the base-class directly.

For this case you don't need to create a $filters-property on the model,
just overwrite the filters() method of the HasFilters trait.

```php 
<?php

namespace App\Models;

use App\Models\Filters\IndividualFilter;
use App\Models\Filters\MySelectFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    public function filters(): Collection
    {
        return collect([
            (new DateFilter('created_at', FilterMode::LOWER))->setQueryName('created_at_lower_filter'),
            (new DateFilter('created_at', FilterMode::GREATER))->setQueryName('created_at_greater_filter'),
            new MySelectFilter(),
            (new StringFilter('title', FilterMode::STARTS_WITH))->setQueryName('starts_with'),
            (new StringFilter('title', FilterMode::ENDS_WITH))->setQueryName('ends_with'),
            (new StringFilter('title', FilterMode::LIKE))->setQueryName('contains'),
            (new StringFilter('title', FilterMode::EQUAL))->setQueryName('equals'),
            (new BooleanFilter(['published']))->setQueryName('boolfilter'),
            new IndividualFilter(),
        ]);
    }
}
```

As you see it is possible to mix directly used filters and dedicated filter classes. If you use multiple filters
of the same type, you must specify the query name to distinguish the given values of the filters.

When using one of the base filter classes, you can just call the setQueryName function on a filter instance. 

To apply some of this filters, just call:

```php 
Post::filter([
    'created_at_lower_filter' => '2023-01-01',
    'starts_with' => 'test',
    'boolfilter' => [
        'published' => true,
    ],
])->get();
```

or open the url

```
https://.../posts?created_at_lower_filter=2023-01-01&starts_with=test&boolfilter[published]=1
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [lacodix](https://github.com/lacodix)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
