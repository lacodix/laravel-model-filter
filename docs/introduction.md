---
title: Introduction
weight: 1
---

This package allows you to filter, search and sort model while fetching from database with ease.
It contains additional functionality to use query strings to filter, search and sort.

Once installed and created some filters (for example a CreatedAfterFilter) you can filter like this:

```bash 
php artisan make:filter CreatedAfterFilter --type=date --field=created_at
```

```php
// Set the filter mode
// App\Models\Filters\CreatedAfterFilter
public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;

// Somwhere in a controller, select all posts created after 1st of January 2023
Post::filter(['created_after_filter' => '2023-01-01'])->get();

// Do the same via query string by calling
// this url: https://.../posts?created_after_filter=2023-01-01
Post::filterByQueryString()->get();
```

and search like this:

```php
// add searchable fields to Model:
// App\Models\Post
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

All filters have a blade template that can visualize the filter with one or multiple input fields.
To visualize all filters of a dedicated model you can use a blade component:

```php
<x-lacodix-filter::model-filters :model="Post::class" />
```