---
title: Testing
weight: 9
---

This package ships with a testing utility that lets you verify the SQL your filters produce —
without hitting the database. This is useful for ensuring filters generate the correct query
structure, bindings, and join counts.

## FilterAssert

The `FilterAssert` class is a framework-agnostic wrapper that works with PHPUnit, Pest, or any
other testing framework. It provides two main assertion methods.

### Installation

`FilterAssert` is included in the package. Just import it in your test files:

```php
use Lacodix\LaravelModelFilter\Testing\FilterAssert;
```

## sqlShape — Verify SQL Structure

Use `FilterAssert::sqlShape()` (or the alias `FilterAssert::shape()`) to assert structural
properties of a query without comparing the full SQL string.

### Parameters

| Parameter | Type | Description |
|---|---|---|
| `$builder` | `Builder\|EloquentBuilder` | The query builder to inspect |
| `$from` | `?string` | Expected FROM table name |
| `$required` | `array` | SQL fragments that must be present |
| `$forbidden` | `array` | SQL fragments that must not be present |
| `$bindings` | `array` | Expected query bindings |
| `$expectedJoins` | `?int` | Expected number of joins |
| `$enforceExactJoinCount` | `bool` | Whether to enforce exact join count (default: `false`, meaning "at least") |

### Basic Example

```php
use App\Models\Post;
use Lacodix\LaravelModelFilter\Testing\FilterAssert;

$query = Post::query();

FilterAssert::shape($query,
    from: 'posts',
    required: ['select'],
    bindings: [],
);
```

### Testing a Date Filter

```php
use App\Models\Post;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Lacodix\LaravelModelFilter\Testing\FilterAssert;

$query = Post::query();

$filter = (new DateFilter())
    ->field('created_at');

$filter->populate('2023-01-01');
$filter->apply($query);

FilterAssert::shape($query,
    from: 'posts',
    required: ['where', 'created_at', 'cast(? as text)'],
    forbidden: [' like '],
    bindings: ['2023-01-01'],
);
```

### Testing a Date Filter with BETWEEN Mode

```php
$query = Post::query();

$filter = (new DateFilter())
    ->field('created_at')
    ->setMode(FilterMode::BETWEEN);

$filter->populate(['2023-01-01', '2023-12-31']);
$filter->apply($query);

FilterAssert::shape($query,
    from: 'posts',
    required: ['where', 'created_at', '>= cast(? as text)', '<= cast(? as text)'],
    forbidden: [' like ', ' join '],
    bindings: ['2023-01-01', '2023-12-31'],
);
```

### Testing a String Filter

```php
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Testing\FilterAssert;

$query = Post::query();

$filter = (new StringFilter())
    ->field('title');

$filter->populate('hello');
$filter->apply($query);

FilterAssert::shape($query,
    from: 'posts',
    required: ['where', '"title"', 'LIKE'],
    forbidden: [' join '],
    bindings: ['%hello%'],
);
```

### Testing a String Filter with EQUAL Mode

```php
$query = Post::query();

$filter = (new StringFilter())
    ->field('title')
    ->setMode(FilterMode::EQUAL);

$filter->populate('hello');
$filter->apply($query);

FilterAssert::shape($query,
    from: 'posts',
    required: ['where', '"title"', '= ?'],
    forbidden: ['LIKE', ' join '],
    bindings: ['hello'],
);
```

### Testing Joins (BelongsToMany Filter)

When testing filters that produce joins, you can verify the join count:

```php
use Lacodix\LaravelModelFilter\Filters\BelongsToManyFilter;
use Lacodix\LaravelModelFilter\Testing\FilterAssert;

$query = Post::query();

// Apply your BelongsToMany filter
$filter->field('tags');
$filter->populate('1');
$filter->apply($query);

FilterAssert::shape($query,
    from: 'posts',
    required: ['exists', 'join', 'tags', 'post_tag'],
    forbidden: [' like '],
    bindings: ['1'],
    expectedJoins: 1,
    enforceExactJoinCount: true,
);
```

Setting `enforceExactJoinCount: true` asserts exactly that many joins. When `false` (the default),
it asserts at least that many joins.

## sqlEquals — Verify Exact SQL

Use `FilterAssert::sqlEquals()` (or the alias `FilterAssert::equals()`) to compare the full
generated SQL string and optionally the bindings.

### Parameters

| Parameter | Type | Description |
|---|---|---|
| `$builder` | `Builder\|EloquentBuilder` | The query builder to inspect |
| `$expectedSql` | `string` | The expected SQL string |
| `$bindings` | `array` | Expected query bindings (optional) |

### Example

```php
use App\Models\Post;
use Lacodix\LaravelModelFilter\Testing\FilterAssert;

$query = Post::query();

FilterAssert::equals($query,
    expectedSql: 'select * from "posts"',
);
```

### Example with Bindings

```php
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Testing\FilterAssert;

$query = Post::query();

$filter = (new StringFilter())
    ->field('title');

$filter->populate('hello');
$filter->apply($query);

FilterAssert::equals($query,
    expectedSql: 'select * from "posts" where "title" LIKE ?',
    bindings: ['%hello%'],
);
```

## Tips

- **No database needed**: These assertions only inspect the generated SQL, they never execute queries.
- **Named parameters**: All methods support named parameters for better readability.
- **Framework-agnostic**: `FilterAssert` works with PHPUnit, Pest, or any test runner — it uses PHPUnit assertions internally.
- **Use `shape` for structural checks**: When you don't care about the exact SQL but want to verify key fragments, bindings, and join counts.
- **Use `equals` for exact matching**: When you need to verify the complete SQL output of a filter.
