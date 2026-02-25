<?php

namespace Lacodix\LaravelModelFilter\Testing;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\Assert;

class SqlAssert
{
    public static function normalizeSql(string $sql): string
    {
        return preg_replace('/\s+/', ' ', trim($sql));
    }

    public static function assertSqlEquals(Builder|EloquentBuilder $builder, string $expectedSql, array $expectedBindings = []): void
    {
        $actualSql = static::normalizeSql($builder->toSql());
        $expectedSql = static::normalizeSql($expectedSql);

        Assert::assertSame($expectedSql, $actualSql, 'SQL does not match expected.');

        if ($expectedBindings !== []) {
            Assert::assertSame($expectedBindings, array_values($builder->getBindings()), 'Bindings do not match expected.');
        }
    }

    public static function extractFromTable(string $sql): ?string
    {
        $normalized = static::normalizeSql($sql);

        // Subselect: from (select ...) → null
        if (preg_match('/\bfrom\s+\(/i', $normalized)) {
            return null;
        }

        // Match: from ["schema".]"table" [as "alias"]
        // Supports: from "users", from users, from "public"."users", from public.users, from users as u
        if (preg_match('/\bfrom\s+(?:"?[a-z0-9_]+"?\.)?"?([a-z0-9_]+)"?(?:\s+as\s+"?[a-z0-9_]+"?)?/i', $normalized, $m)) {
            return $m[1];
        }

        return null;
    }

    public static function assertSqlShape(Builder|EloquentBuilder $builder, array $shape): void
    {
        $sql = static::normalizeSql($builder->toSql());

        if (isset($shape['from'])) {
            $from = static::extractFromTable($sql);
            Assert::assertSame($shape['from'], $from, "Expected FROM table '{$shape['from']}', got '" . ($from ?? 'null') . "'.");
        }

        if (isset($shape['required'])) {
            foreach ($shape['required'] as $fragment) {
                Assert::assertStringContainsString(
                    static::normalizeSql($fragment),
                    $sql,
                    "Required fragment '{$fragment}' not found in SQL: {$sql}"
                );
            }
        }

        if (isset($shape['forbidden'])) {
            foreach ($shape['forbidden'] as $fragment) {
                Assert::assertStringNotContainsString(
                    static::normalizeSql($fragment),
                    $sql,
                    "Forbidden fragment '{$fragment}' found in SQL: {$sql}"
                );
            }
        }

        if (isset($shape['bindings'])) {
            Assert::assertSame($shape['bindings'], array_values($builder->getBindings()), 'Bindings do not match expected.');
        }

        if (isset($shape['expectedJoins'])) {
            $joinCount = preg_match_all('/\bjoin\b/i', $sql);
            $enforceExact = $shape['enforceExactJoinCount'] ?? false;

            if ($enforceExact) {
                Assert::assertSame($shape['expectedJoins'], $joinCount, "Expected exactly {$shape['expectedJoins']} join(s), found {$joinCount}.");
            } else {
                Assert::assertGreaterThanOrEqual($shape['expectedJoins'], $joinCount, "Expected at least {$shape['expectedJoins']} join(s), found {$joinCount}.");
            }
        }
    }
}
