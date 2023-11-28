---
title: Filter for Relation Aggregations
weight: 5
---

All filtering methods base on the assumption that the fields for filtering are available
in the database. Sometimes you want to filter for fields of model relations or even
do filtering on complex calculated attributes of the model.

To use this package with such fields, it is necessary to transfer calculations to the database.
This has in many cases the advantage to optimize your database queries and reduce load.

All of the following ideas are based on the [Laracast](https://www.laracasts.com) series
[Eloquent Performance patterns](https://laracasts.com/series/eloquent-performance-patterns) by
[Jonathan Reinink](https://reinink.ca/).
We highly recommend you watching this series for multiple ideas of increase performance of your
database queries and you will also have multiple ideas of using more and more filter options with this
package.

## Virtual Columns

With laravel you can easily integrate virtual fields to your database tables.
Given a rating model, where users can rate for different properties like service, cost, speed and more.
The database table contains values for each of this single properties, and in your application 
you need to calculate the average overall on several places. You might do this with PHP, but if 
you want to filter for the overall rating, you have to do this on the database.

In such cases you can just add a virtual field 'overall' on your ratings table that 
calculates the average of all single values on the fly.

```php
Schema::table('ratings', function (Blueprint $table) {
    $table->decimal('overall')->virtualAs('(`speed`+`service`+`cost`)/3');
});
```

This will add an overall field to all of your models that is also available in database queries.

```php 
// App\Models\Rating
use HasFilters;
protected array $filters = [
    RatingFilter::class,
];

// App\Models\Filters\RatingFilter 
class RatingFilter extends NumericFilter
{
    public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;

    protected string $field = 'overall';
}
```

## Add relations with scopes

You are free to add scopes in your filter classes that also executes joins and subqueries on the database. Every filter
has a query-function that applies the filter to the database query.

Given the Rating model from the above example, and additional a Vendor model. Every vendor can have multiple ratings.
If You want to filter the vendors depending on the average ratings or on the number of ratings, either you must do 
it on PHP side, or add a more complex database query to your filtering.

First of all for convenience create a scope on the vendor model (Given you also used the virtual field of the example
above)

```php
// App\Models\Vendor
public function scopeWithAverageRatings(Builder $query): Builder
{
    // Create Subquery
    $averageRatings = Rating::query()
        ->select([
            'vendor_id',
            DB::raw('avg(`speed`) as `avg_speed`'),
            DB::raw('avg(`service`) as `avg_service`'),
            DB::raw('avg(`cost`) as `avg_cost`'),
            DB::raw('avg(`overall`) as `avg_overall`'),
            DB::raw('count(`cost`) as `rating_count`'),
        ])
        ->groupBy('vendor_id');

    return $query
        ->leftJoinSub($averageRatings, 'ratings', function ($join) {
            $join->on('vendors.id', '=', 'ratings.vendor_id');
        });
}
```

Applying this scope to your vendor queries will always add the vendors average ratings to the vendor model, so you can
use it in the filter, search and sorting.

```php
// App\Models\Vendor
protected array $sortable = [
    'rating_count',
    'avg_overall',
];

// Somewhere in a controller
Vendor::withAverageRatings()->sort(['avg_overall' => 'desc'])->get();
```

Or use it in a Numeric filter 


```php 
// App\Models\Vendor
use HasFilters;
protected array $filters = [
    RatingFilter::class,
];

// App\Models\Filters\RatingFilter 
class RatingFilter extends NumericFilter
{
    public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;

    protected string $field = 'avg_overall';
}
```
