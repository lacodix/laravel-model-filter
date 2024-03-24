---
title: Belongs To Many Timeframe Filter
weight: 5
---

Sometimes you have relations between your models with pivot dates. For example when you have users and teams
and there is a pivot date for joining and leaving a team. Another example is when your users have multiple
membership statuses and this statuses can change over time. If you do not only need the current situation
but also historical data, then you add a start and end timestamp to your pivot relation data.

If you have such a relation you might need to filter by this pivot table. Maybe you need all users that
are member in one or multiple teams. Maybe you also need to find all members of a team at a dedicated timeframe.
Or you need to find all members that have joined or left a team in a dedicated timeframe.

If you just need the information if a user was ever part of a team you can stick with the simple BelongsToMany filter.
But for all other cases you can use this really mighty BelongsToManyTimeframe Filter.

Out of the box you get all of this functionality:

![Belongs To Many Timeframe Filter](https://www.lacodix.de/imgs/docs/belongs_to_many_timeframe_filter.png)

The field name is used for the relation on the base model.

## Create the filter

```bash
php artisan make:filter TestBelongsToManyTimeframeFilter -t belongs-to-many-timeframe -f tags --relation="\App\Models\Tag" --title=title --start_field=start_date --end_field=end_date
```

this creates a filter class that extends BelongsToManyTimeframeFilter

```php
<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Filters\BelongsToManyTimeframeFilter;

class TestBelongsToManyTimeframeFilter extends BelongsToManyTimeframeFilter
{
    protected string $field = 'tags';
    protected string $startField = 'start_date';
    protected string $endField = 'end_date';

    protected string $relationModel = \App\Models\Tag::class;
    protected string $titleColumn = 'title';
}

```

Basically this will setup a normal Belongs To Many filter that contains additional functionality.
Please see the docs for more information about belongs to many, belongs to and select filters that are
extended by this filter.

You can use all functionality like Multiselect, mapping of titles an extending the base query.

## Use the filter

Applying this filter differs from other filters, because it has a mode and date values additionally
to the filter values. Thats why filtering for the relations needs some additional information. The 
values you want to filter for are entered in the values key:

To filter programmatically use the filter-scope

```php 
Post::filter(['test_belongs_to_many_timeframe_filter' => ['values' => ...]])->get();
```

or by query string

```
https://.../posts?test_belongs_to_many_timeframe_filter[values]=...
```

## Timeframe Filter Mode

Additionally to the normal filter mode, this filter has a second mode to specify how the filter
calculates its result in combination with the pivot date values. The filter has five possible
timeframe modes:

- TimeframeFilterMode::CURRENT (current)
- TimeframeFilterMode::EVER (ever)
- TimeframeFilterMode::TIMEFRAME (timeframe)
- TimeframeFilterMode::START_IN_TIMEFRAME (start_in_timeframe)
- TimeframeFilterMode::END_IN_TIMEFRAME (end_in_timeframe)

This modes are selectable over the ui component and by parameter to the filter method.

You can create a filter that fixes this mode to a dedicated value if you want, but then you also 
need to create a ui component for it, since the default timeframe component adds a radio button group
for this mode.

to switch the mode programmatically you can just add it to the filter array

```php
Post::filter(['test_belongs_to_many_timeframe_filter' => ['values' => ..., 'mode' => 'current']])->get();
```

or by query string

```
https://.../posts?test_belongs_to_many_timeframe_filter[values]=...&test_belongs_to_many_timeframe_filter[mode]=current
```


### TimeframeFilterMode::CURRENT

This mode will find all entries that are currently related, given by the start and end values.
It also takes in account null values, so if no start is given, it is assumed somewhere in the 
past, and if no end is given this means it is still related. 

The current time can be precised as day, month or current year (see precision setting below).
The default is current month.

### TimeframeFilterMode::EVER

This mode doesn't care about the start and end date. If you only need this value you can just use
the default belongs to many filter.

### TimeframeFilterMode::TIMEFRAME

This mode behaves like the current value, but you can select the beginn and end of the timeframe. 
It also takes in account the precision and validates against the from and to value. If precision is set
to month, it expects a month in the format yyyy-mm, with precision of a day it expects a date yyyy-mm-dd
and for years it just expects a year number.

```php
Post::filter(['test_belongs_to_many_timeframe_filter' => [
    'values' => ..., 
    'mode' => 'timeframe',
    'from' => '2024-01',
    'to' => '2024-02',
]])->get();
```
This example will filter all models where the relation exists on January and/or February of 2024.

or by query string

```
https://.../posts?test_belongs_to_many_timeframe_filter[values]=...
    &test_belongs_to_many_timeframe_filter[mode]=timeframe
    &test_belongs_to_many_timeframe_filter[from]=2024-01
    &test_belongs_to_many_timeframe_filter[to]=2024-02
```

### TimeframeFilterMode::START_IN_TIMEFRAME, TimeframeFilterMode::END_IN_TIMEFRAME

This modes don't filter for the existence of the relation in a given timeframe, but the filter just for the start
or the end date. So you can find relations that started or ended in the given timeframe.

## Filter precision

The precision can be set programmatically but not over the UI. Nevertheless you can create your own component
that also offers the precision to the user.

- TimeframeFilterPrecision::DAY
- TimeframeFilterPrecision::MONTH (default)
- TimeframeFilterPrecision::YEAR

Just overwrite the $precision property of the filter:

```php
use Lacodix\LaravelModelFilter\Enums\TimeframeFilterPrecision;

protected TimeframeFilterPrecision $precision = TimeframeFilterPrecision::YEAR;
```

Or if you already have a instance of a filter class, you can change it programmatically 

```php
$myFilter->setPrecision(TimeframeFilterPrecision::YEAR)
```

## Translations

The Timeframe mode needs translation strings for the UI radio button group. You can publish the translation
files and overwrite it in general, or do it for each filter by overwriting the `getTimeframeModeLabel` method:
```php 
    public function getTimeframeModeLabel(TimeframeFilterMode $mode): string
    {
        return match ($mode) {
            TimeframeFilterMode::CURRENT => trans('model-filter::filters.current'),
            TimeframeFilterMode::EVER => trans('model-filter::filters.ever'),
            TimeframeFilterMode::TIMEFRAME => trans('model-filter::filters.in_timeframe'),
            TimeframeFilterMode::START_IN_TIMEFRAME => trans('model-filter::filters.start_in_timeframe'),
            TimeframeFilterMode::END_IN_TIMEFRAME => trans('model-filter::filters.end_in_timeframe'),
        };
    }
```

## Filter Modes

- FilterMode::EQUAL
- FilterMode::CONTAINS
