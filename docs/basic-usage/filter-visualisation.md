---
title: Filter Visualisation
weight: 5
---

To use your filters in your web application you have to offer the filter options to your visitors.
You can just use the query parameters or create your own forms and input fields, but for convenience 
this packet ships with a set of views for each filter-type. Each view creates a simple input with headline.

## Filter form

Additionally it comes with a blade component for integration of all filters of a model.

```html
<x-lacodix-filter::model-filters :model="Post::class" />
```

you can set the model via a class name string, or via an instance of the model.

```html
<x-lacodix-filter::model-filters :model="$post" />
```

This will result in the following HTML code given the following example classes below

```html 
<form method="get">
    <div class="filter-container select">
        <div class="filter-title">
            Post Type Filter
        </div>

        <div class="filter-content">
            <select class="filter-input" name="post_type_filter" onchange="this.form.submit()">
                <option value="">&mdash;</option>
                <option value="page">page</option>
                <option value="post">post</option>
            </select>
        </div>
    </div>
</form>
```

```php
File: App\Models\Filters\PostTypeFilter  
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\SelectFilter;

class PostTypeFilter extends SelectFilter
{
    protected string $field = 'type';

    public function options(): array
    {
        return [
            'page',
            'post',
        ];
    }
}



File: App\Models\Post  
<?php

namespace App\Models;

use App\Models\Filters\PostTypeFilter;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class Post extends Model
{
    use HasFilters;

    protected array $filters = [
        PostTypeFilter::class,
    ];
}

```

## Form attributes

You can also add attributes to the form and change the method, if needed, like so:

```html
<x-lacodix-filter::model-filters :model="Post::class" method="post" :action="route('posts')" />
```

## Change headline

To change the headline of the filter visualisation just overwrite the $title property of the filter
or if you need translations you can also overwrite the title()-Method.

```php
    protected string $title = 'My Filter';
```

```php
    public function title(): string
    {
        return __('My Filter');
    }
```

## CSS styles for filter

You can style all filters with the following classes:

.filter-container is the surrounding div of any filter type. Cou can specify the filter type
with the corresponding classes like boolean, select, text, numeric, date:
e.g.: .filter-container.select

```css 
.filter-container {

}
.filter-container.select {
    
}
```

more available classes are filter-title, filter-content and filter-input that addresses the corresponding
div or input element in the views

```css 
.filter-title,
.filter-content {
    width: 100%;
}

.filter-input {
    width: 100%;
    padding: 5px;
}
```

## Change available views

You can publish the views of this package and change all filter blade files for your needs. See installation
section for more information.

## Using own components

You can change the component used by a filter, by overwriting a filters $component property.
If you for example create an individual filter you can decide if it will use one of the
given components, or you can also select an individual component with one of the following
options:

```php 
    ...
    protected string $component = 'select';
```

This will search for a select-component in the packages components filters views folder:
resources\views\vendor\lacodix-filter\components\filters\select.blade.php

```php 
    ...
    public function component(): string
    {
        return 'my-component';
    }
```

This will use the individual component <x-my-component> for the filters view.
