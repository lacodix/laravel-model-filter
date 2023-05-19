---
title: Enum Filter
weight: 5
---

The EnumFilter is a special variation of the SelectFilter that automatically offers all
cases out of an PHP Enum.

## Create the filter

```bash
php artisan make:filter TestEnumFilter -t enum -f fieldname
```

this creates a filter class that extends EnumFilter, and you have to add the enum name to the
$enum property like in this example. The enum must be a string backed enum. 

```php
<?php

namespace App\Models\Filters;

use App\Models\Enums\ActiveState;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;

class TestEnumFilter extends EnumFilter
{
    protected string $field = 'state';

    protected string $enum = 'ActiveState::class';
}
```

The Enum could for example have values like this
```
<?php

namespace App\Models\Enums;

enum ActiveState: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
```

A fieldname must be set, this will apply a where query to the model-query
where fieldname equals the given value. The possible values are automatically extracted
out of the enum - in this case it will be 'active' and 'inactive'. The database field
state should contain one of this both values.

## Visualisation

For Visualisation of the select values you can set a translation string prefix. If you
have translations for the both strings (active, inactive) in a translation file named
users.php and prefixed with 'status_', this means your translation strings have the
keys users.status_active and users.status_inactive. To automatically add the translated
strings to your select filter views, just add the $translationPrefix property to your
filter.

```php
<?php

namespace App\Models\Filters;

use App\Models\Enums\ActiveState;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;

class TestEnumFilter extends EnumFilter
{
    protected string $field = 'state';

    protected string $enum = 'ActiveState::class';
    protected string $translationPrefix = 'users.status_';
}
```

## Filter Modes

Enum filters only have the EQUAL mode like SelectFilters.
