<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Lacodix\LaravelModelFilter\Testing\SqlAssert;
use PHPUnit\Framework\ExpectationFailedException;

function makeBuilder(string $table = 'users'): Builder
{
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getTablePrefix')->andReturn('');
    $grammar = new SQLiteGrammar($connection);
    $processor = new SQLiteProcessor;
    $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);
    $connection->shouldReceive('getPostProcessor')->andReturn($processor);

    return new Builder($connection, $grammar, $processor);
}

// a) normalizeSql stabilisiert Whitespace
it('normalizes whitespace in SQL', function () {
    expect(SqlAssert::normalizeSql("  select  *   from   users  \n  where  id = 1  "))
        ->toBe('select * from users where id = 1');
});

it('normalizes tabs and newlines', function () {
    expect(SqlAssert::normalizeSql("select\t*\nfrom\tusers"))
        ->toBe('select * from users');
});

// b) assertSqlEquals schlägt fehl bei anderer Reihenfolge/Bindings
it('assertSqlEquals passes for matching SQL', function () {
    $builder = makeBuilder('users')->select('*')->from('users')->where('id', '=', 1);

    SqlAssert::assertSqlEquals($builder, 'select * from "users" where "id" = ?', [1]);
});

it('assertSqlEquals fails for non-matching SQL', function () {
    $builder = makeBuilder('users')->select('*')->from('users')->where('id', '=', 1);

    SqlAssert::assertSqlEquals($builder, 'select * from "posts" where "id" = ?');
})->throws(ExpectationFailedException::class);

it('assertSqlEquals fails for wrong bindings', function () {
    $builder = makeBuilder('users')->select('*')->from('users')->where('id', '=', 1);

    SqlAssert::assertSqlEquals($builder, 'select * from "users" where "id" = ?', [99]);
})->throws(ExpectationFailedException::class);

// c) assertSqlShape: from, required, forbidden, bindings
it('assertSqlShape validates from table', function () {
    $builder = makeBuilder()->select('*')->from('users');

    SqlAssert::assertSqlShape($builder, ['from' => 'users']);
});

it('assertSqlShape fails on wrong from table', function () {
    $builder = makeBuilder()->select('*')->from('users');

    SqlAssert::assertSqlShape($builder, ['from' => 'posts']);
})->throws(ExpectationFailedException::class);

it('assertSqlShape validates required fragments', function () {
    $builder = makeBuilder()->select('*')->from('users')->where('name', '=', 'test');

    SqlAssert::assertSqlShape($builder, [
        'required' => ['where', '"name"'],
    ]);
});

it('assertSqlShape fails on missing required fragment', function () {
    $builder = makeBuilder()->select('*')->from('users');

    SqlAssert::assertSqlShape($builder, [
        'required' => ['where'],
    ]);
})->throws(ExpectationFailedException::class);

it('assertSqlShape validates forbidden fragments', function () {
    $builder = makeBuilder()->select('*')->from('users');

    SqlAssert::assertSqlShape($builder, [
        'forbidden' => ['join', 'where'],
    ]);
});

it('assertSqlShape fails when forbidden fragment is present', function () {
    $builder = makeBuilder()->select('*')->from('users')->where('id', '=', 1);

    SqlAssert::assertSqlShape($builder, [
        'forbidden' => ['where'],
    ]);
})->throws(ExpectationFailedException::class);

it('assertSqlShape validates bindings', function () {
    $builder = makeBuilder()->select('*')->from('users')->where('id', '=', 5);

    SqlAssert::assertSqlShape($builder, [
        'bindings' => [5],
    ]);
});

it('assertSqlShape fails on wrong bindings', function () {
    $builder = makeBuilder()->select('*')->from('users')->where('id', '=', 5);

    SqlAssert::assertSqlShape($builder, [
        'bindings' => [99],
    ]);
})->throws(ExpectationFailedException::class);

// d) join count enforcement
it('assertSqlShape validates expectedJoins (at least)', function () {
    $builder = makeBuilder()->select('*')->from('users')
        ->join('posts', 'users.id', '=', 'posts.user_id');

    SqlAssert::assertSqlShape($builder, [
        'expectedJoins' => 1,
    ]);
});

it('assertSqlShape enforceExactJoinCount passes on exact match', function () {
    $builder = makeBuilder()->select('*')->from('users')
        ->join('posts', 'users.id', '=', 'posts.user_id');

    SqlAssert::assertSqlShape($builder, [
        'expectedJoins' => 1,
        'enforceExactJoinCount' => true,
    ]);
});

it('assertSqlShape enforceExactJoinCount fails on mismatch', function () {
    $builder = makeBuilder()->select('*')->from('users')
        ->join('posts', 'users.id', '=', 'posts.user_id')
        ->join('comments', 'posts.id', '=', 'comments.post_id');

    SqlAssert::assertSqlShape($builder, [
        'expectedJoins' => 1,
        'enforceExactJoinCount' => true,
    ]);
})->throws(ExpectationFailedException::class);

// expectedJoins "at least" fails when joinCount < expectedJoins
it('assertSqlShape expectedJoins at least fails when not enough joins', function () {
    $builder = makeBuilder()->select('*')->from('users')
        ->join('posts', 'users.id', '=', 'posts.user_id');

    SqlAssert::assertSqlShape($builder, [
        'expectedJoins' => 2,
    ]);
})->throws(ExpectationFailedException::class);

// FROM extraction with alias
it('assertSqlShape extracts from table with alias', function () {
    $builder = makeBuilder()->select('*')->from('users as u');

    SqlAssert::assertSqlShape($builder, ['from' => 'users']);
});

// Schema-qualified from
it('extractFromTable handles schema-qualified table', function () {
    expect(SqlAssert::extractFromTable('select * from "public"."users" where id = 1'))
        ->toBe('users');
});

it('extractFromTable handles unquoted schema-qualified table', function () {
    expect(SqlAssert::extractFromTable('select * from public.users where id = 1'))
        ->toBe('users');
});

it('extractFromTable returns null for subselect', function () {
    expect(SqlAssert::extractFromTable('select * from (select * from users) as sub'))
        ->toBeNull();
});

it('assertSqlShape fails on subselect when from is set', function () {
    $sql = 'select * from (select * from users) as sub';
    $from = SqlAssert::extractFromTable($sql);
    expect($from)->toBeNull();

    // Build a mock builder that returns subselect SQL
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('toSql')->andReturn($sql);
    $builder->shouldReceive('getBindings')->andReturn([]);

    SqlAssert::assertSqlShape($builder, ['from' => 'users']);
})->throws(ExpectationFailedException::class);

// Pest expectations
it('pest expectation toHaveSqlEquals works', function () {
    $builder = makeBuilder()->select('*')->from('users')->where('id', '=', 1);

    expect($builder)->toHaveSqlEquals('select * from "users" where "id" = ?', [1]);
});

it('pest expectation toHaveSqlShape works', function () {
    $builder = makeBuilder()->select('*')->from('users')
        ->join('posts', 'users.id', '=', 'posts.user_id')
        ->where('name', '=', 'test');

    expect($builder)->toHaveSqlShape([
        'from' => 'users',
        'required' => ['join', 'where'],
        'forbidden' => ['delete'],
        'bindings' => ['test'],
        'expectedJoins' => 1,
        'enforceExactJoinCount' => true,
    ]);
});
