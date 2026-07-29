---
title: Filter Presets
weight: 6
---

Sometimes a free input is technically right but tedious in practice. If a user filters
people by age over and over again, they don't want to type `18` and `27` every time -
they want to pick "youth" and be done with it, while keeping the option to enter their
own values.

That's what presets are for: a list of pre-configured values next to the regular input.
Every filter can carry them, but only views that render them show them - of the shipped
ones that is the **numeric** filter (see the last section).

## Adding presets

Either fluently ...

```php
(new NumericFilter('age'))
    ->setQueryName('age')
    ->setMode(FilterMode::BETWEEN)
    ->setPresets([
        ['label' => 'Up to 18', 'values' => ['', 18]],
        ['label' => '19 - 27', 'values' => [19, 27]],
        ['label' => '28 and older', 'values' => [28, '']],
    ]);
```

... or by overriding `definePresets()`, which is the way to go for presets that are
built dynamically, e.g. from a configuration:

```php
class AgeFilter extends NumericFilter
{
    public FilterMode $mode = FilterMode::BETWEEN;

    protected string $field = 'age';

    protected function definePresets(): array
    {
        return collect(config('members.age_thresholds'))
            ->map(fn (int $threshold, int $index) => [
                'label' => __('Up to :age', ['age' => $threshold]),
                'values' => [$index === 0 ? '' : config('members.age_thresholds')[$index - 1] + 1, $threshold],
            ])
            ->all();
    }
}
```

Both ways are validated: a preset without a `label` or without `values` throws an
`InvalidArgumentException` instead of ending up as an "Undefined array key" somewhere
inside a view. That is why `presets()` itself is `final` - it is the accessor that
validates, `definePresets()` is the place to build them.

Note that overriding `definePresets()` takes over the presets entirely: the default
implementation is what returns the fluently set ones, so `setPresets()` has no effect
on such a filter unless the override includes `parent::definePresets()`.

The `values` of a preset are exactly what the filter would receive from the input
itself - a single value for single value modes, two values for the between modes.

## A preset is only a shortcut

Picking a preset writes its values into the very same filter value the manual input
writes into. **Nothing about the preset itself is stored** - not in the query string,
not anywhere else. Filtering by the preset "19 - 27" leads to the very same result,
the very same URL and the very same SQL as typing 19 and 27 by hand:

```
https://.../people?age[0]=19&age[1]=27
```

This is a deliberate decision, because it keeps the meaning of a filter link stable.
A bookmarked or shared link keeps filtering for the ages it was created for, even if
the presets behind it are changed later on.

Consequently presets need no identity: the index of an entry is all that is needed
while rendering, and it never leaves the rendering.

## Rendering

The shipped `numeric` filter view renders the presets as a radio group above the
regular inputs, together with two generic entries:

- **All** - resets the filter values, nothing is filtered.
- **Custom** - reveals the regular inputs to enter own values.

Which one is preselected is derived from the current filter values. Filters offer two
methods for that, useful when rendering presets in your own views:

```php
$filter->presetState($values);  // 'none', the index of the matching preset, or 'custom'
$filter->activePreset($values); // the index of the matching preset, or null
```

Both compare values regardless of their type, so a preset written as `[19, 27]` is
still recognized in the `['19', '27']` a request delivers.

Because the selection is derived, picking **Custom** while the values still belong to a
preset only opens the inputs - it does not change what is filtered for. Reloading the
page then shows that preset selected again, which is correct: the filter still filters
for exactly its values. The values are deliberately left untouched when opening the
custom inputs, so that a preset can be used as the starting point for own values.

The radios themselves are never submitted: their `form` attribute points at an id no
form has, which leaves them without a form owner. No form submits them - regardless of
what triggers the submit - while they keep grouping by their `name`. So the query string
stays free of the positional preset index, see "A preset is only a shortcut" above.

The id lives in one place, `Support\DetachedForm::id()`, and is a namespaced constant
(`lmf-detached-controls`). Should the surrounding application ever carry a form with
exactly that id, it would adopt the radios and submit them after all - so don't use that
id for a form of your own.

## One filter set per page

Not having a form owner also means the radios group by their `name` across the whole
document instead of per form. Rendering the **same** filter set twice on one page (e.g. a
desktop and a mobile variant) therefore merges the two preset groups into one: picking a
preset in the first panel deselects the one in the second.

That scenario is not supported anyway, with or without presets: the value inputs carry
ids derived from the query name (`age_from`), so they exist twice as well and
`getElementById` always finds the first one - a preset in the second panel would write
into the inputs of the first. Render a filter set once per page, or give the second one
its own query names via `setQueryName()`.

## Only rendered where a view renders them

Presets are available on every filter type, but of the shipped views only the **numeric**
one renders them. Adding presets to e.g. a string filter is a silent no-op - so only add
them to a filter whose view renders them, or render them yourself using `presetState()`
in your own view.
