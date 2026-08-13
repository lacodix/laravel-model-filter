<?php

declare(strict_types=1);

use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Filters\OptionFilter;
use Tests\Models\Shape\InputContractPost;
use Tests\Support\InputContractFilterFactory;
use Tests\Support\InputContractTestSupport;

dataset('option input validation modes', [
    'filter' => ValidationMode::FILTER,
    'throw' => ValidationMode::THROW,
]);

dataset('option filter malformed inputs', [
    'scalar' => ['1', 'shape_filter'],
    'indexed list' => [['1'], 'shape_filter'],
    'nested option' => [['published' => ['1']], 'shape_filter.published'],
]);

it('rejects malformed option maps without casting their values', function (
    string|array $input,
    string $errorPath,
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('option')
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
})->with('option filter malformed inputs')->with('option input validation modes');

it('keeps valid option maps and boolean casts', function (string $input, bool $binding) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('option')
        ->setQueryName('shape_filter')
        ->setModel($model);
    $query = $model->newQuery();

    $filter->populate(['published' => $input])->apply($query);

    expect($query->toSql())->toContain('where')
        ->and($query->getBindings())->toBe([$binding]);
})->with([
    'true' => ['1', true],
    'false' => ['0', false],
    'documented false string cast' => ['false', true],
]);

it('continues to ignore unknown option keys', function (ValidationMode $validationMode) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('option')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();

    $filter->populate([
        'published' => '1',
        'unknown' => [['ignored']],
    ])->apply($query);

    expect($query->getBindings())->toBe([true]);
})->with('option input validation modes');

it('accepts an empty option map as an absent filter', function (ValidationMode $validationMode) {
    $model = new InputContractPost;
    $filter = InputContractFilterFactory::make('option')
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();
    $before = InputContractTestSupport::snapshot($query);

    $filter->populate([])->apply($query);

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with('option input validation modes');

it('guards both keys and applied values of associative options', function (
    string $optionName,
    ValidationMode $validationMode
) {
    $model = new InputContractPost;
    $filter = (new OptionFilter(['published' => 'Published']))
        ->setQueryName('shape_filter')
        ->setModel($model)
        ->setValidationMode($validationMode);
    $query = $model->newQuery();
    $before = InputContractTestSupport::snapshot($query);

    if ($validationMode === ValidationMode::THROW) {
        $exception = InputContractTestSupport::validationException(
            fn () => $filter->populate([$optionName => ['1']])->apply($query)
        );

        expect(array_keys($exception->errors()))->toContain('shape_filter.'.$optionName);
    } else {
        $filter->populate([$optionName => ['1']])->apply($query);
    }

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with(['published', 'Published'])->with('option input validation modes');
