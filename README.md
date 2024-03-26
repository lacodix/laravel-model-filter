# laravel-model-filter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lacodix/laravel-model-filter.svg?style=flat-square)](https://packagist.org/packages/lacodix/laravel-model-filter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/lacodix/laravel-model-filter/test.yaml?branch=master&label=tests&style=flat-square)](https://github.com/lacodix/laravel-model-filter/actions?query=workflow%3Atest+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/lacodix/laravel-model-filter/style.yaml?branch=master&label=code%20style&style=flat-square)](https://github.com/lacodix/laravel-model-filter/actions?query=workflow%3Astyle+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/lacodix/laravel-model-filter.svg?style=flat-square)](https://packagist.org/packages/lacodix/laravel-model-filter)

This package allows you to filter, search and sort models while fetching from database with ease.
It contains additional functionality to use query strings to filter, search and sort.

Once installed you can filter, search and sort Models.
You can create own filters based on our base filters, create individual filters, or use
one of the extended filters, that are ready to use with less or even without configuration like TrashedFilter.

Additionally you can use the visualisation functionality of filters.

## Documentation

You can find the entire documentation for this package on [our documentation site](https://www.lacodix.de/docs/laravel-model-filter)

## Installation

```bash
composer require lacodix/laravel-model-filter
```

## Basic Usage

### Filter

Create your first filter

```bash 
php artisan make:filter CreatedAfterFilter --type=date --field=created_at
```

```php
// Set the filter mode
// App\Models\Filters\CreatedAfterFilter
public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;

// Apply this filter and the HasFilters trait to a Model
// App\Models\Post
use HasFilters;
protected array $filters = [
    CreatedAfterFilter::class,
];

// Somwhere in a controller, select all posts created after 1st of January 2023
Post::filter(['created_after_filter' => '2023-01-01'])->get();

// Do the same via query string by calling
// this url: https://.../posts?created_after_filter=2023-01-01
Post::filterByQueryString()->get();
```

### Search

```php
// add searchable fields and the IsSearchable trait to Model:
// App\Models\Post
use IsSearchable;
protected array $searchable = [
    'title',
    'content',
];

// Somewhere in controller, find all posts that contain "test" in title or content
Post::search('test')->get();

// Do the same via query string by calling
// this url: https://.../posts?search=test
Post::searchByQueryString()->get();
```

### Visualize

All filters have a blade template that can visualize the filter with one or multiple input fields.
To visualize all filters of a dedicated model you can use a blade component:

```php
<x-lacodix-filter::model-filters :model="Post::class" />
```

### Grouping

Sometimes you don't need all of the filters for all parts of a web application. Maybe there shall
be different filters be available to the backend as in the frontend, or different user types
shall be able to use different filters.

For such cases this package offers filter grouping when adding filters to models

```php
protected array $filters = [
    'frontend' => [
        HotFilter::class,
    ],
    'backend' => [
        CreatedAfterFilter::class,
        PublishedFilter::class,
    ]
];
```

The groups can be used in the scopes

```php
Post::filterByQueryString('frontend')->get()
```
or
```php 
Post::filter(['hot_filter' => 'hot'], 'frontend')->get();
Post::filter(['created_after_filter' => '2023-01-01'], 'backend')->get();
```

## Testing

```bash
composer test
```

## Contributing

Please run the following commands and solve potential problems before committing
and think about adding tests for new functionality.

```bash
composer rector:test
composer insights
composer csfixer:test
composer phpstan:test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [lacodix](https://github.com/lacodix)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
