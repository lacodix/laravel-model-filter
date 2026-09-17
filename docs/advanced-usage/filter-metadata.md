---
title: Filter Metadata
weight: 8
---

A filter knows how to narrow a query. How it is *shown* - as a dropdown, a row of
tabs, a grid of avatars - is the business of whoever renders it: a Blade view of your
own, an admin package such as `lacodix/resourcerer`, a JSON API. Metadata is the
channel between the two: free, serializable arrays that travel with the filter and
that this package stores without ever interpreting them.

There are two levels:

- **Filter meta** (`meta()` / `getMeta()`) - about the filter as a whole, on every
  filter type. A presentation hint, layout options, an icon name.
- **Option meta** (`optionMeta()` / `getOptionMeta()` / `optionMetaFor()`) - one
  array per option value of a select-shaped filter (`SelectFilter` and its
  descendants such as `EnumFilter`, `BelongsToFilter`, `TrashedFilter`) and of the
  `OptionFilter`. A picture, initials, a colour, a subtitle.

Filtering, validation and the query string know nothing about either; a filter with
metadata filters exactly like one without.

## Filter meta

```php
(new SelectFilter('assignee_id'))
    ->setQueryName('assignee')
    ->setOptions(['Ada' => 7, 'Grace' => 12])
    ->meta(['presentation' => 'avatars'])
    ->meta(['columns' => 4]);

$filter->getMeta(); // ['presentation' => 'avatars', 'columns' => 4]
```

Repeated calls merge with `array_replace_recursive()`, so a class default and a
fluent addition end up side by side:

```php
class AssigneeFilter extends SelectFilter
{
    protected string $field = 'assignee_id';

    protected array $meta = ['presentation' => 'avatars'];
}

(new AssigneeFilter)->meta(['columns' => 4])->getMeta();
// ['presentation' => 'avatars', 'columns' => 4]
```

Keys and values are yours. Keep them serializable (scalars and arrays) - a consumer
may hand them to a view, a JSON response or a Livewire component.

## Option meta

Option meta is keyed by the option **values** as `options()` returns them - not by
the labels, so that it survives translated or renamed labels. Unknown values simply
carry no meta:

```php
$filter->optionMeta([
    7 => ['avatar' => 'https://cdn.example/ada.jpg', 'subtitle' => 'Engineering'],
    12 => ['initials' => 'GH', 'color' => '#7c3aed'],
]);

$filter->optionMetaFor(7);     // ['avatar' => '...', 'subtitle' => 'Engineering']
$filter->optionMetaFor('7');   // the same - values are matched by their string form
$filter->optionMetaFor(99);    // []
$filter->optionMetaFor(Status::Open); // a backed enum case addresses its value
```

Options are often database-backed, so the map may be a closure. It is resolved
lazily - the first time somebody asks - and only once per filter instance. The
closure receives the filter, so it can build on the resolved `options()`:

```php
(new BelongsToFilter('assignee_id'))
    ->setRelationModel(User::class)
    ->setTitleColumn('name')
    ->optionMeta(fn (BelongsToFilter $filter) => User::query()
        ->whereIn('id', $filter->options())
        ->get()
        ->mapWithKeys(fn (User $user) => [$user->id => [
            'avatar' => $user->profile_photo_url,
            'subtitle' => $user->team?->name,
        ]])
        ->all());
```

Filter classes override `defineOptionMeta()` instead - like `definePresets()` it
takes over entirely and keeps the shape check in place:

```php
class AssigneeFilter extends BelongsToFilter
{
    protected function defineOptionMeta(): array
    {
        return User::query()
            ->whereIn('id', $this->options())
            ->get()
            ->mapWithKeys(fn (User $user) => [$user->id => ['avatar' => $user->profile_photo_url]])
            ->all();
    }
}
```

The map is resolved once per filter instance. A preparation instance
(`newPreparationInstance()`, see [Opt-in Filter Preparation](filter-preparation.md))
resets the model and relation it was resolved against and therefore resolves its
own map again on first use.

Every entry must be an array; anything else throws an `InvalidArgumentException`
when the map is resolved, so that a malformed entry fails loudly instead of ending
up as an "Undefined array key" inside a view. A repeated `optionMeta()` call
replaces the previous map.

## Use case: avatar filters

A "person" filter - assignee, author, member of a family - reads much better as a
row of faces than as a dropdown of names. The package side is nothing but metadata:

```php
(new BelongsToFilter('person_id'))
    ->setQueryName('person')
    ->setRelationModel(Person::class)
    ->setTitleColumn('name')
    ->meta(['presentation' => 'avatars'])
    ->optionMeta(fn (BelongsToFilter $filter) => Person::query()
        ->whereIn('id', $filter->options())
        ->get()
        ->mapWithKeys(fn (Person $person) => [$person->id => [
            'avatar' => $person->photo_url,      // an image URL, or null
            'initials' => $person->initials,     // shown when there is no picture
            'color' => $person->color,           // a CSS colour for the initials tile
            'subtitle' => $person->club?->name,  // a small line under the name
        ]])
        ->all());
```

The keys `presentation`, `avatar`, `initials`, `color` and `subtitle` are a
convention **of the consumer** - `lacodix/resourcerer` renders such a filter as avatar
tiles in its filter drawer and as an avatar row of quick filters, filling in
initials and a colour where the meta leaves them out. This package neither knows nor
checks those keys; it only makes sure they arrive where the filter is rendered.
Whatever you render yourself, read the meta the same way:

```blade
@foreach ($filter->options() as $label => $value)
    @php($meta = $filter->optionMetaFor($value))
    <label>
        <input type="radio" name="{{ $filter->queryName() }}" value="{{ $value }}">
        @if ($meta['avatar'] ?? null)
            <img src="{{ $meta['avatar'] }}" alt="{{ $label }}">
        @else
            <span>{{ $meta['initials'] ?? mb_substr($label, 0, 2) }}</span>
        @endif
        {{ $label }}
    </label>
@endforeach
```

Treat option meta as data, never as markup: a URL and text go through the usual
escaping of your template engine.
