---
title: Opt-in Filter Preparation
weight: 7
---

Some integrations need to know which model filters would reach `Filter::apply()` before they
open a query context of their own. `FilterPreparation` exposes that decision without changing
the existing model scopes and without applying anything to a query.

## Preparing filters

Pass a model instance, the complete filter payload, and an explicit group:

```php
use App\Models\Post;
use Lacodix\LaravelModelFilter\Support\FilterPreparation;

$prepared = (new FilterPreparation)->prepare(
    model: new Post,
    values: $request->all(),
    group: 'backend',
);
```

The model must expose a `filters()` method that returns an array; otherwise preparation throws
`InvalidArgumentException`.

The service resolves new objects for class-string definitions and creates unpopulated copies
of object definitions. On those copies, it resets values, the validator, the model binding,
the relation query, and resolved presets. Other configured properties remain intact. In
particular, `$options` is preserved because the same property represents explicit
`setOptions()` configuration and a lazy cache in some filters. A previously resolved options
snapshot can therefore remain unless the configurator replaces it. Preparation does not use
the objects cached by `filterInstances()`. Only visible filters with a known, present query
name are considered. A top-level `null` or empty string is absent; `false` and `0` remain valid
submitted values.

For each candidate, preparation runs `applicable()`, scope population and normalization, the
configured `ValidationMode`, input-shape validation, and `readyToApply()`. In
`ValidationMode::FILTER`, invalid filters are omitted. In `ValidationMode::THROW`, the same
Laravel `ValidationException` is thrown as by the model scope.

`PreparedFilters` is an iterable, countable value object:

```php
$prepared->isEmpty();
$prepared->count();
$prepared->queryNames(); // list<string>
$prepared->all();        // list<Filter>

foreach ($prepared as $filter) {
    // Each filter is fresh and already populated.
}
```

Its query names can only come from the supplied payload. Preparation never calls `apply()` or
changes a builder. Whether a prepared filter adds predicates, is a no-op, or changes another
part of a builder remains a decision for the consuming integration.

## Strict group resolution

By default, group lookup keeps the legacy behavior: an unknown named group falls back to
`__default` when that group exists. Enable strict lookup when an integration must not open a
context for the wrong group:

```php
$prepared = (new FilterPreparation)->prepare(
    model: new Post,
    values: $values,
    group: 'backend',
    strictGroup: true,
);
```

An unknown group then throws `UnknownFilterGroupException`. For an ungrouped filter list,
`FilterPreparation::DEFAULT_GROUP` is the only group accepted in strict mode. The group stays
an explicit argument; integrations can use that constant instead of spelling `__default`.
The behavior of `filter()`, `filterByQueryString()`, and `filterInstances()` is unchanged.

## Configuring fresh instances

An integration can configure the originally resolved instance before the package evaluates
`visible()`, and every fresh instance before it evaluates `options()`, applicability,
population, or validation:

```php
use App\Models\Filters\ContextAwareFilter;
use Lacodix\LaravelModelFilter\Filters\Filter;

$prepared = (new FilterPreparation)->prepare(
    model: new Post,
    values: $values,
    group: 'backend',
    strictGroup: true,
    configure: static function (Filter $filter) use ($contextId): void {
        if ($filter instanceof ContextAwareFilter) {
            $filter->setContextId($contextId);
        }
    },
);
```

The callback runs for every resolved fresh instance, including one whose query name is not in
the payload. When the legacy `mapFilter` macro replaces a filter with a distinct instance, the
replacement is also copied using the same reset rules, configured, and bound to the model
before candidate processing. For that filter definition, the callback therefore runs twice in
total: once on the original resolved instance and once on the replacement. Callbacks with
external side effects should account for both invocations. For parity with `filterInstances()`,
`visible()` is evaluated on the original instance before mapping and is not evaluated again on
the replacement. Configuration and population therefore stay isolated from UI instances
returned by `filterInstances()`.

`Filter::readyToApply()` is also public for consumers that already own a populated filter. It
only exposes the existing protected `shouldApply()` decision and does not apply the filter. In
`ValidationMode::THROW`, it can throw Laravel's `ValidationException` when input-shape
validation fails, just like `shouldApply()` does during normal application.
