<?php

declare(strict_types=1);

use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Tests\Models\Shape\InputContractPost;
use Tests\Support\InputContractFilterFactory;
use Tests\Support\InputContractTestSupport;

dataset('input contract validation modes', [
    'filter' => ValidationMode::FILTER,
    'throw' => ValidationMode::THROW,
]);

dataset('scalar filter malformed inputs', [
    'string with a list' => ['string', ['needle'], 'shape_filter'],
    'boolean with a list' => ['boolean', ['1'], 'shape_filter'],
    'numeric with a list' => ['numeric', ['10'], 'shape_filter'],
    'date with a list' => ['date', ['2026-01-01'], 'shape_filter'],
    'select with a list' => ['select', ['page'], 'shape_filter'],
    'enum with a list' => ['enum', ['page'], 'shape_filter'],
    'belongs-to with a list' => ['belongs_to', ['page'], 'shape_filter'],
    'belongs-to-many with a list' => ['belongs_to_many', ['1'], 'shape_filter'],
    'trashed with a list' => ['trashed', ['only_trashed'], 'shape_filter'],
]);

dataset('list filter malformed inputs', [
    'numeric range with nested value' => ['numeric_range', [['10'], '20'], 'shape_filter.0'],
    'date range with nested value' => [
        'date_range',
        [['2026-01-01'], '2026-12-31'],
        'shape_filter.0',
    ],
    'select list with nested value' => ['select_multi', [['page']], 'shape_filter.0'],
    'select list with scalar value' => ['select_multi', 'page', 'shape_filter'],
    'enum list with nested value' => ['enum_multi', [['page']], 'shape_filter.0'],
    'enum list with scalar value' => ['enum_multi', 'page', 'shape_filter'],
    'belongs-to list with nested value' => ['belongs_to_multi', [['page']], 'shape_filter.0'],
    'belongs-to list with scalar value' => ['belongs_to_multi', 'page', 'shape_filter'],
    'belongs-to-many list with nested value' => [
        'belongs_to_many_multi',
        [['1']],
        'shape_filter.0',
    ],
    'belongs-to-many list with scalar value' => [
        'belongs_to_many_multi',
        '1',
        'shape_filter',
    ],
]);

dataset('valid scalar filter inputs', [
    'string' => ['string', 'needle', ['%needle%']],
    'boolean true' => ['boolean', '1', [true]],
    'boolean false' => ['boolean', '0', [false]],
    'numeric' => ['numeric', '10', ['10']],
    'date' => ['date', '2026-01-01', ['2026-01-01']],
    'select' => ['select', 'page', ['page']],
    'enum' => ['enum', 'page', ['page']],
    'belongs-to' => ['belongs_to', 'page', ['page']],
]);

dataset('valid list filter inputs', [
    'numeric range' => ['numeric_range', ['10', '20'], ['10', '20']],
    'numeric range with named keys' => [
        'numeric_range',
        ['minimum' => '10', 'maximum' => '20'],
        ['10', '20'],
    ],
    'date range' => [
        'date_range',
        ['2026-01-01', '2026-12-31'],
        ['2026-01-01', '2026-12-31'],
    ],
    'date range with named keys' => [
        'date_range',
        ['from' => '2026-01-01', 'to' => '2026-12-31'],
        ['2026-01-01', '2026-12-31'],
    ],
    'select list' => ['select_multi', ['page', 'post'], ['page', 'post']],
    'select list with named keys' => [
        'select_multi',
        ['first' => 'page', 'second' => 'post'],
        ['page', 'post'],
    ],
    'enum list' => ['enum_multi', ['page', 'post'], ['page', 'post']],
    'enum list with named keys' => [
        'enum_multi',
        ['first' => 'page', 'second' => 'post'],
        ['page', 'post'],
    ],
    'belongs-to list' => ['belongs_to_multi', ['page', 'post'], ['page', 'post']],
    'belongs-to list with named keys' => [
        'belongs_to_multi',
        ['first' => 'page', 'second' => 'post'],
        ['page', 'post'],
    ],
    'belongs-to-many list' => ['belongs_to_many_multi', ['1', '2'], ['1', '2']],
    'belongs-to-many list with named keys' => [
        'belongs_to_many_multi',
        ['first' => '1', 'second' => '2'],
        ['1', '2'],
    ],
]);

dataset('empty multi-select inputs', [
    'select' => ['select_multi', 'where'],
    'enum' => ['enum_multi', 'where'],
    'belongs-to' => ['belongs_to_multi', 'where'],
    'belongs-to-many' => ['belongs_to_many_multi', 'exists'],
]);

it('rejects malformed scalar filter shapes without changing the query', function (
    string $family,
    mixed $input,
    string $errorPath,
    ValidationMode $validationMode
) {
    $model = InputContractTestSupport::model($family);
    $filter = InputContractFilterFactory::make($family)
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();
    $before = InputContractTestSupport::snapshot($query);

    $filter->populate($input);

    if ($validationMode === ValidationMode::THROW) {
        $exception = InputContractTestSupport::validationException(
            fn () => InputContractTestSupport::withPhpWarningsAsExceptions(
                fn () => $filter->apply($query)
            )
        );

        expect(array_keys($exception->errors()))->toContain($errorPath);
    } else {
        InputContractTestSupport::withPhpWarningsAsExceptions(fn () => $filter->apply($query));
    }

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with('scalar filter malformed inputs')->with('input contract validation modes');

it('rejects malformed list filter shapes without predicates or joins', function (
    string $family,
    string|array $input,
    string $errorPath,
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make($family)
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();
    $before = InputContractTestSupport::snapshot($query);

    $filter->populate($input);

    if ($validationMode === ValidationMode::THROW) {
        $exception = InputContractTestSupport::validationException(
            fn () => InputContractTestSupport::withPhpWarningsAsExceptions(
                fn () => $filter->apply($query)
            )
        );

        expect(array_keys($exception->errors()))->toContain($errorPath);
    } else {
        InputContractTestSupport::withPhpWarningsAsExceptions(fn () => $filter->apply($query));
    }

    expect(InputContractTestSupport::snapshot($query))->toBe($before)
        ->and(strtolower($query->toSql()))->not->toContain(' join ', ' where ', ' exists ');
})->with('list filter malformed inputs')->with('input contract validation modes');

it('preserves valid scalar filter SQL and bindings', function (
    string $family,
    string $input,
    array $bindings
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make($family)
        ->setQueryName('shape_filter')
        ->setModel($model);
    $query = $model->newQuery();

    $filter->populate($input)->apply($query);

    expect($query->toSql())->toContain('where')
        ->and($query->getBindings())->toBe($bindings);
})->with('valid scalar filter inputs');

it('preserves valid list filter SQL and bindings', function (
    string $family,
    array $input,
    array $bindings
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make($family)
        ->setQueryName('shape_filter')
        ->setModel($model);
    $query = $model->newQuery();

    $filter->populate($input)->apply($query);

    expect($query->toSql())->toContain('where')
        ->and($query->getBindings())->toBe($bindings);
})->with('valid list filter inputs');

it('preserves the single-field envelope accepted by populate', function () {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('string')
        ->setQueryName('shape_filter')
        ->setModel($model);
    $query = $model->newQuery();

    $filter->populate(['shape_filter' => 'needle'])->apply($query);

    expect($filter->getValues())->toBe(['shape_filter' => 'needle'])
        ->and($query->getBindings())->toBe(['%needle%']);
});

it('preserves the existing semantics of an empty multi-select', function (
    string $family,
    string $sqlFragment,
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make($family)
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();

    $filter->populate([])->apply($query);

    expect(strtolower($query->toSql()))->toContain($sqlFragment)
        ->and($query->getBindings())->toBe([]);
})->with('empty multi-select inputs')->with('input contract validation modes');

it('preserves the direct empty-range behavior', function (
    string $family,
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make($family)
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();
    $before = InputContractTestSupport::snapshot($query);

    $filter->populate([])->apply($query);

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with(['numeric_range', 'date_range'])->with('input contract validation modes');

it('treats an absent direct input as a no-op', function (string $family) {
    $model = InputContractTestSupport::model($family);
    $filter = InputContractFilterFactory::make($family)
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode(ValidationMode::THROW);
    $query = $model->newQuery();
    $before = InputContractTestSupport::snapshot($query);

    $filter->populate(null)->apply($query);

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with([
    'string',
    'boolean',
    'numeric',
    'date',
    'select',
    'enum',
    'option',
    'trashed',
    'belongs_to',
    'belongs_to_many',
]);

it('does not reuse validation results after repopulating a filter', function () {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('numeric')
        ->setQueryName('shape_filter')
        ->setModel($model);
    $invalidQuery = $model->newQuery();
    $validQuery = $model->newQuery();

    $filter->populate(['10']);
    expect($filter->fails())->toBeTrue();
    $filter->apply($invalidQuery);

    $filter->populate('10');
    expect($filter->fails())->toBeFalse();
    $filter->apply($validQuery);

    expect(InputContractTestSupport::snapshot($invalidQuery))->toBe(InputContractTestSupport::snapshot($model->newQuery()))
        ->and($validQuery->getBindings())->toBe(['10']);

    $secondInvalidQuery = $model->newQuery();
    $filter->populate(['20']);
    expect($filter->fails())->toBeTrue();
    $filter->apply($secondInvalidQuery);

    expect(InputContractTestSupport::snapshot($secondInvalidQuery))
        ->toBe(InputContractTestSupport::snapshot($model->newQuery()));
});

it('keeps direct application independent from prior semantic validation', function (
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('date')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode)
        ->populate('not-a-date');
    $beforeValidation = $model->newQuery();
    $afterValidation = $model->newQuery();

    $filter->apply($beforeValidation);

    expect($filter->fails())->toBeTrue();

    $filter->apply($afterValidation);

    expect(InputContractTestSupport::snapshot($afterValidation))
        ->toBe(InputContractTestSupport::snapshot($beforeValidation))
        ->and($afterValidation->getBindings())->toBe(['not-a-date']);
})->with('input contract validation modes');

it('preserves the public required rules of numeric and date filters', function (string $family) {
    $filter = InputContractFilterFactory::make($family)->setQueryName('shape_filter');

    expect($filter->rules()['shape_filter'])->toStartWith('required|');
})->with(['numeric', 'numeric_range', 'date', 'date_range']);

it('uses custom messages and validation attributes for shape errors', function () {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('string')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode(ValidationMode::THROW);
    $filter->messages = ['input_shape' => 'Malformed :attribute.'];
    $filter->validationAttributes = ['shape_filter' => 'custom filter'];

    $exception = InputContractTestSupport::validationException(
        fn () => $filter->populate([['needle']])->apply($model->newQuery())
    );

    expect($exception->errors()['shape_filter'])->toBe(['Malformed custom filter.']);
});

it('does not use the shape fallback for semantic rules on an input_shape query name', function () {
    $filter = InputContractFilterFactory::make('numeric')
        ->setQueryName('input_shape')
        ->populate('not-a-number');

    $exception = InputContractTestSupport::validationException(fn () => $filter->validate());

    expect($exception->errors()['input_shape'])
        ->toBe(['The input shape field must be a number.']);
});
