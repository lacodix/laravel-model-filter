---
title: Prepopulation Trait
weight: 2
---

The Prepopulation trait extends the functionality of a SelectFilter. Like the EnumFilter that automatically offers
all cases out of an PHP Enum, this Prepopulation trait automatically loads all select options out of the database.

Given you have a field of vendor names and the name is saved in a database field named 'vendor', and you need a
filter that offers all existing vendors in a select box, you can just extend your SelectFilter with the 
Prepopulation trait. 

## Usage

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Filters\Traits\Prepopulation;

class PrepopulatedSelectFilter extends SelectFilter
{
    use Prepopulation;
    
    protected string $field = 'vendor';
}
```

## Options mapping

If you need to map the values, maybe because of translations, you can just add the `mapOption` method.

```php 
protected function mapOption(string $option): string
{
    return __('translations.' . $option);
}
```
