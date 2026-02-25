<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/


uses(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

use Lacodix\LaravelModelFilter\Testing\SqlAssert;

expect()->extend('toHaveSqlEquals', function (string $expectedSql, array $expectedBindings = []) {
    SqlAssert::assertSqlEquals($this->value, $expectedSql, $expectedBindings);

    return $this;
});

expect()->extend('toHaveSqlShape', function (array $shape) {
    SqlAssert::assertSqlShape($this->value, $shape);

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/
