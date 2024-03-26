---
title: Installation
weight: 3
---

You can install the package via composer:

```bash
composer require lacodix/laravel-model-filter
```

## Config file

The package brings a config file and views with translations, that can be published.

```bash
php artisan vendor:publish --tag="model-filter-config"
```

This is the content of the published config file:

```php
return [
    'date_format' => 'Y-m-d',
    'search_query_value_name' => 'search',
    'search_query_fields_name' => 'search_for',
];
```

## Views for filter components

You can publish the view components with:

```bash
php artisan vendor:publish --tag="lacodix-filter-views"
```

## Translation files for extended filters

You can publish the translation files with:

```bash
php artisan vendor:publish --tag="model-filter-translations"
```

Translations are only needed, if you use one of the extended filters like the
TrashedFilter.
