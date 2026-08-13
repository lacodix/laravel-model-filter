<?php

declare(strict_types=1);

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Tests\Models\Shape\InputContractPost;
use Tests\Support\InputContractFilterFactory;
use Tests\Support\InputContractTestSupport;

dataset('timeframe input validation modes', [
    'filter' => ValidationMode::FILTER,
    'throw' => ValidationMode::THROW,
]);

dataset('timeframe filter malformed inputs', [
    'top-level scalar' => ['timeframe', '1', 'shape_filter'],
    'top-level list' => ['timeframe', ['1'], 'shape_filter'],
    'array mode' => [
        'timeframe',
        ['mode' => ['current'], 'values' => '1'],
        'shape_filter.mode',
    ],
    'array from' => [
        'timeframe',
        ['mode' => 'timeframe', 'values' => '1', 'from' => ['2026-01'], 'to' => '2026-02'],
        'shape_filter.from',
    ],
    'array to' => [
        'timeframe',
        ['mode' => 'timeframe', 'values' => '1', 'from' => '2026-01', 'to' => ['2026-02']],
        'shape_filter.to',
    ],
    'nested equal values' => [
        'timeframe',
        ['mode' => 'ever', 'values' => [['1']]],
        'shape_filter.values',
    ],
    'named equal values' => [
        'timeframe',
        ['mode' => 'ever', 'values' => ['first' => '1']],
        'shape_filter.values',
    ],
    'nested multi values' => [
        'timeframe_multi',
        ['mode' => 'ever', 'values' => [['1']]],
        'shape_filter.values.0',
    ],
    'scalar multi values' => [
        'timeframe_multi',
        ['mode' => 'ever', 'values' => '1'],
        'shape_filter.values',
    ],
]);

dataset('timeframe inputs without relation values', [
    'empty payload keeps ever semantics' => [[], 'exists', 0],
    'current without values keeps timeframe semantics' => [['mode' => 'current'], 'exists', 2],
]);

dataset('timeframe inverted empty inputs', [
    'never with empty list' => [['mode' => 'never', 'values' => []], 'not exists', 0],
    'never without values key' => [['mode' => 'never'], 'not exists', 0],
    'not-current with empty list' => [['mode' => 'not_current', 'values' => []], 'not exists', 2],
    'not-current without values key' => [['mode' => 'not_current'], 'not exists', 2],
]);

it('rejects malformed timeframe payloads without a relation predicate', function (
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
})->with('timeframe filter malformed inputs')->with('timeframe input validation modes');

it('keeps optional timeframe relation values backward compatible', function (
    array $input,
    string $sqlFragment,
    int $bindingCount,
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('timeframe')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();

    $filter->populate($input)->apply($query);

    expect(strtolower($query->toSql()))->toContain($sqlFragment)
        ->and($query->getBindings())->toHaveCount($bindingCount);
})->with('timeframe inputs without relation values')->with('timeframe input validation modes');

it('keeps inverted timeframe modes valid without selected values', function (
    array $input,
    string $sqlFragment,
    int $bindingCount,
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('timeframe')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();

    $filter->populate($input)->apply($query);

    expect(strtolower($query->toSql()))->toContain($sqlFragment)
        ->and($query->getBindings())->toHaveCount($bindingCount);
})->with('timeframe inverted empty inputs')->with('timeframe input validation modes');

it('preserves the existing inverted equal-list behavior', function (
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('timeframe')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();

    $filter->populate(['mode' => 'never', 'values' => ['1']]);

    expect($filter->fails())->toBeFalse();

    $filter->apply($query);

    expect($filter->getValue())->toBe(['mode' => 'never', 'values' => ['1']])
        ->and(strtolower($query->toSql()))->toContain('not exists')
        ->and($query->getBindings())->toBe([]);
})->with('timeframe input validation modes');

it('preserves the ever fallback for an unknown scalar mode', function (
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('timeframe')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();

    $filter->populate(['mode' => 'sometimes', 'values' => '1']);

    expect($filter->fails())->toBeFalse();

    $filter->apply($query);

    expect($filter->timeframeFilterMode()->value)->toBe('ever')
        ->and(strtolower($query->toSql()))->toContain('exists')
        ->and($query->getBindings())->toBe(['1']);
})->with('timeframe input validation modes');

it('preserves associative scalar values in timeframe multiselect mode', function (
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('timeframe_multi')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();

    $filter->populate([
        'mode' => 'ever',
        'values' => ['first' => '1', 'second' => '2'],
    ])->apply($query);

    expect(strtolower($query->toSql()))->toContain('exists', ' in ')
        ->and($query->getBindings())->toBe(['1', '2']);
})->with('timeframe input validation modes');

it('continues to ignore unknown timeframe payload keys', function (
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('timeframe')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();

    $filter->populate([
        'mode' => 'ever',
        'values' => '1',
        'unknown' => [['ignored']],
    ])->apply($query);

    expect(strtolower($query->toSql()))->toContain('exists')
        ->and($query->getBindings())->toBe(['1']);
})->with('timeframe input validation modes');

it('uses the same list shape as its public rules for every non-equal filter mode', function () {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('timeframe')
        ->setMode(FilterMode::LOWER)
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->populate(['mode' => 'ever', 'values' => ['1']]);
    $query = $model->newQuery();

    expect($filter->fails())->toBeFalse();

    $filter->apply($query);

    expect(strtolower($query->toSql()))->toContain('exists')
        ->and($query->getBindings())->toBe([]);
});

it('reports a scalar timeframe root only once during full validation', function () {
    $filter = InputContractFilterFactory::make('timeframe')
        ->setQueryName('shape_filter')
        ->populate('1');

    $exception = InputContractTestSupport::validationException(fn () => $filter->validate());

    expect($exception->errors()['shape_filter'])->toHaveCount(1);
});
