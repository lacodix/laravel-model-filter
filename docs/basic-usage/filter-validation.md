---
title: Filter Validation
weight: 6
---

Some filters include validation. DateFilters for example will validate the inserted values for
the given format 'Y-m-d'. The used format can be overwritten with the config value 'date_format'.
If a given value does not fit the validation, an ValidationException will be thrown.

NumericFilters validates the input for numerical values. You can also add minimum and maximum check
for the numeric filters by setting the properties $min and/or $max on the NumericFilter.

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\NumericFilter;

class TestNumericFilter extends NumericFilter
{
    protected string $field = 'fieldname';

    protected int $min = 100;
    protected int $max = 1000;
}
```

SelectFilter validate against the available options.

## Validation Mode

Usually invalid values are just ignored. If invalid values are set, the filter is just not applied.
You can switch the validation mode to a hard validation, to throw a ValidationException with invalid data.
Just set the Validation Mode of your Filter like in the following example.

```php
    ...
    public ValidationMode $validationMode = ValidationMode::THROW;
    ...
```

## Own Validation Rules

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

Depending on the ValidationMode of your filter, the filter will not be applied (default) or throw an exception.

## Customize Error Messages & Attributes

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

if you don't know the field name before runtime, you can also overwrite the corresponding methods
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
