---
title: Filter input contract
weight: 7
---

Filter values can come from `filter()`, `filterByQueryString()`, or from direct
`populate(...)->apply(...)` usage. All built-in filters validate the input structure on
these paths before adding a query predicate. Applications and UI packages do not need to
call a separate normalization method.

Pass the value of one filter to `populate()`. The existing single-field envelope form remains
supported for backward compatibility:

```php
$filter->populate('published');
$filter->populate(['published', 'draft']);
$filter->populate(['status' => 'published']); // query name: status
```

Model scopes have already selected one filter's raw value. An associative array received there is
therefore validated as that value, not reinterpreted as an envelope.

## Supported shapes

| Filter | Mode | Supported input |
|---|---|---|
| `StringFilter` | `EQUAL`, `LIKE`, `STARTS_WITH`, `ENDS_WITH`, `NOT_CONTAINS` | One scalar value |
| `BooleanFilter` | `EQUAL` | One boolean or bool-castable scalar value |
| `NumericFilter` | Single-value modes | One numeric scalar |
| `NumericFilter` | Between/not-between modes | An array with exactly two scalar boundaries |
| `DateFilter` | Single-value modes | One scalar matching the configured date format |
| `DateFilter` | Between/not-between modes | An array with exactly two scalar date boundaries |
| `SelectFilter` | `EQUAL` | One scalar option value |
| `SelectFilter` | `CONTAINS`, `NOT_CONTAINS` | An array of scalar option values |
| `EnumFilter` | Select modes | The same shapes as `SelectFilter`; values must be backed enum values |
| `BelongsToFilter` | Select modes | The same shapes as `SelectFilter`; values must be available relation IDs |
| `BelongsToManyFilter` | Select modes | The same shapes as `SelectFilter`; values must be available relation IDs |
| `OptionFilter` | — | An associative map of configured option names to scalar or `null` values |
| `TrashedFilter` | — | `with_trashed` or `only_trashed` |
| `BelongsToManyTimeframeFilter` | See below | An associative timeframe payload |

An omitted value or a top-level `null` is treated as no filter by the model scopes and by direct
application of the built-in single-field filters in both validation modes. Filters using
`RunsOnRelation` retain their historical direct behavior: `populate(null)->apply()` still adds the
relation query. The model scopes do not invoke an omitted filter and also continue to ignore a
top-level empty scalar string. `NumericFilter::rules()` and `DateFilter::rules()` retain their
existing `required` rules for applications that consume those arrays separately. Empty strings
inside a two-value numeric or date range are supported and mean that the corresponding boundary is
open:

```php
Post::filter(['age' => ['', 18]])->get();
Post::filter(['age' => [65, '']])->get();
```

Range keys are ignored as before; boundaries are normalized in insertion order. Nested
boundary values are rejected.

Flat associative multiselect arrays also remain accepted; their keys do not affect the query.
Empty multiselect lists remain valid and keep their existing query semantics (for example,
`whereIn(..., [])`). Scalars are not converted into multiselect lists.

### Option filter

`OptionFilter` interprets configured keys from an associative map:

```php
Post::filter([
    'visibility' => [
        'published' => '1',
        'featured' => '0',
    ],
])->get();
```

An empty map is treated as no filter. Indexed lists and nested values for configured
options are rejected. Unknown option names continue to be ignored. Boolean casting
remains unchanged: in PHP the string `false` is truthy; use `0` for false in a query
string.

## Timeframe payload

`BelongsToManyTimeframeFilter` accepts these keys:

```php
[
    'mode' => 'timeframe',
    'values' => ['1', '2'],
    'from' => '2026-01',
    'to' => '2026-12',
]
```

- `mode` may be omitted, which means `ever`. Recognized values are `current`, `ever`,
  `timeframe`, `start_in_timeframe`, `end_in_timeframe`, `never`, and `not_current`.
- In `FilterMode::EQUAL`, `values` is a scalar relation ID.
- In `FilterMode::CONTAINS` and `FilterMode::NOT_CONTAINS`, `values` is an array of
  scalar values.
- `values` remains optional in every mode. Omitting it keeps the existing
  relationship-existence semantics; it does not make the filter itself required.
- `never` and `not_current` deliberately allow `values` to be omitted, `null`, or an
  empty list. This keeps the documented “no relation at all” and “no currently active
  relation” queries available.
- For backward compatibility, flat arrays in inverted `FilterMode::EQUAL` payloads retain their
  existing behavior. New code should pass a scalar when selecting one relation ID.
- `from` and `to` are scalar values and are validated for the configured day, month, or
  year precision.

The root payload must be associative unless it is the existing empty payload shorthand. Unknown
scalar modes retain the existing fallback to `ever`. Arrays in `mode`, `from`, or `to`, and nested
value arrays are rejected. Additional payload keys continue to be ignored.

## Invalid input

Both validation modes use the same shape contract:

| Validation mode | Result |
|---|---|
| `ValidationMode::FILTER` | The invalid filter is skipped. It adds no predicate or relation join. |
| `ValidationMode::THROW` | Laravel throws a `ValidationException`. Error keys identify the invalid path, for example `membership.mode`, `membership.from`, or `membership.values.0`. |

The model scopes continue to apply the filter's existing `rules()` together with this shape
contract. Direct `populate(...)->apply(...)` automatically checks the structural contract; an
explicit `fails()` or `validate()` continues to evaluate the complete public rule set.

Malformed arrays are never converted to strings, booleans, or numbers. They are also not
passed to enum constructors, date parsers, `array_intersect()`, or query-builder
comparisons.

## Custom filters

`Filter` itself does not prescribe a value shape because custom filters may need custom
payloads. Existing `rules()` remain the semantic contract for custom filters.
`SingleFieldFilter` supplies the scalar contract automatically, while its built-in range
and select descendants specialize it to flat arrays. Custom filters with structured
payloads can extend the same validation path by overriding `validateInputShape()`. Override
`applyFilter()` for query logic so direct application continues through the validation guard.
Existing custom filters that override `apply()` keep their historical behavior for backward
compatibility and therefore replace the direct-application guard as well. Model scopes still run
their normal validation before calling such a filter. Moving only the query logic to
`applyFilter()` opts direct calls into the structural guard.

Input validation is not authorization. Available options and application permissions
remain the responsibility of the filter and application exactly as before.
