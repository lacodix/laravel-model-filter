<?php

namespace Lacodix\LaravelModelFilter\Testing;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class FilterAssert
{
    /**
     * Assert that the builder's SQL matches a given shape.
     *
     * @param Builder|EloquentBuilder $builder
     * @param string|null $from Expected FROM table name
     * @param array<string> $required SQL fragments that must be present
     * @param array<string> $forbidden SQL fragments that must not be present
     * @param array<mixed> $bindings Expected query bindings
     * @param int|null $expectedJoins Expected number of joins
     * @param bool $enforceExactJoinCount Whether to enforce exact join count
     */
    public static function sqlShape(
        Builder|EloquentBuilder $builder,
        ?string $from = null,
        array $required = [],
        array $forbidden = [],
        array $bindings = [],
        ?int $expectedJoins = null,
        bool $enforceExactJoinCount = false,
    ): void {
        SqlAssert::assertSqlShape($builder, [
            'from' => $from,
            'required' => $required,
            'forbidden' => $forbidden,
            'bindings' => $bindings,
            'expectedJoins' => $expectedJoins,
            'enforceExactJoinCount' => $enforceExactJoinCount,
        ]);
    }

    /**
     * Assert that the builder's SQL exactly matches the expected SQL.
     *
     * @param Builder|EloquentBuilder $builder
     * @param string $expectedSql Expected SQL string
     * @param array<mixed> $bindings Expected query bindings
     */
    public static function sqlEquals(
        Builder|EloquentBuilder $builder,
        string $expectedSql,
        array $bindings = [],
    ): void {
        SqlAssert::assertSqlEquals($builder, $expectedSql, $bindings);
    }

    /**
     * Alias for sqlShape().
     *
     * @see self::sqlShape()
     */
    public static function shape(
        Builder|EloquentBuilder $builder,
        ?string $from = null,
        array $required = [],
        array $forbidden = [],
        array $bindings = [],
        ?int $expectedJoins = null,
        bool $enforceExactJoinCount = false,
    ): void {
        static::sqlShape($builder, $from, $required, $forbidden, $bindings, $expectedJoins, $enforceExactJoinCount);
    }

    /**
     * Alias for sqlEquals().
     *
     * @see self::sqlEquals()
     */
    public static function equals(
        Builder|EloquentBuilder $builder,
        string $expectedSql,
        array $bindings = [],
    ): void {
        static::sqlEquals($builder, $expectedSql, $bindings);
    }
}
