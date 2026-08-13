<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Tests\Models\Post;
use Tests\Models\Shape\InputContractPost;
use Tests\Models\Shape\InputContractSoftDeletePost;
use Tests\Models\Tag;
use Tests\Support\InputContractFilterFactory;
use Tests\Support\InputContractTestSupport;

dataset('scope input validation modes', [
    'filter' => ValidationMode::FILTER,
    'throw' => ValidationMode::THROW,
]);

dataset('scope malformed built-in inputs', [
    'string' => ['string', ['needle'], 'shape_filter'],
    'string associative raw value' => [
        'string',
        ['shape_filter' => 'needle'],
        'shape_filter',
    ],
    'boolean' => ['boolean', ['1'], 'shape_filter'],
    'numeric range' => ['numeric_range', [['10'], '20'], 'shape_filter.0'],
    'date range' => [
        'date_range',
        [['2026-01-01'], '2026-12-31'],
        'shape_filter.0',
    ],
    'select' => ['select_multi', [['page']], 'shape_filter.0'],
    'enum' => ['enum', ['page'], 'shape_filter'],
    'option' => ['option', ['published' => ['1']], 'shape_filter.published'],
    'trashed' => ['trashed', ['only_trashed'], 'shape_filter'],
    'belongs-to' => ['belongs_to', ['page'], 'shape_filter'],
    'belongs-to-many' => ['belongs_to_many_multi', [['1']], 'shape_filter.0'],
    'timeframe' => [
        'timeframe',
        ['mode' => ['current'], 'values' => '1'],
        'shape_filter.mode',
    ],
]);

beforeEach(function () {
    InputContractPost::$configuredFilters = [];
    InputContractSoftDeletePost::$configuredFilters = [];
});

it('enforces every built-in shape through scopeFilter', function (
    string $family,
    mixed $input,
    string $errorPath,
    ValidationMode $validationMode
) {
    $filter = InputContractFilterFactory::make($family)
        ->setQueryName('shape_filter')
        ->setValidationMode($validationMode);

    if ($family === 'trashed') {
        InputContractSoftDeletePost::$configuredFilters = [$filter];
        InputContractSoftDeletePost::query()->create(['title' => 'first']);
        InputContractSoftDeletePost::query()->create(['title' => 'second']);
        $query = InputContractSoftDeletePost::query();
        $expectedIds = InputContractSoftDeletePost::query()->orderBy('id')->pluck('id')->all();
    } else {
        InputContractPost::$configuredFilters = [$filter];
        InputContractPost::query()->create([
            'title' => 'first',
            'type' => 'page',
            'published' => true,
            'content' => 'first',
            'counter' => 10,
        ]);
        InputContractPost::query()->create([
            'title' => 'second',
            'type' => 'post',
            'published' => false,
            'content' => 'second',
            'counter' => 20,
        ]);
        $query = InputContractPost::query();
        $expectedIds = InputContractPost::query()->orderBy('id')->pluck('id')->all();
    }

    $before = InputContractTestSupport::snapshot($query);

    if ($validationMode === ValidationMode::THROW) {
        $exception = InputContractTestSupport::validationException(
            fn () => InputContractTestSupport::withPhpWarningsAsExceptions(
                fn () => $query->filter(['shape_filter' => $input])
            )
        );

        expect(array_keys($exception->errors()))->toContain($errorPath);
    } else {
        InputContractTestSupport::withPhpWarningsAsExceptions(
            fn () => $query->filter(['shape_filter' => $input])
        );

        expect((clone $query)->orderBy('id')->pluck('id')->all())->toBe($expectedIds);
    }

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with('scope malformed built-in inputs')->with('scope input validation modes');

it('normalizes scalar multi-select scope values before validation and application', function () {
    $filter = InputContractFilterFactory::make('select_multi')->setQueryName('shape_filter');
    InputContractPost::$configuredFilters = [$filter];
    $matching = InputContractPost::query()->create([
        'title' => 'matching',
        'type' => 'page',
        'published' => true,
        'content' => 'matching',
        'counter' => 10,
    ]);
    InputContractPost::query()->create([
        'title' => 'other',
        'type' => 'post',
        'published' => false,
        'content' => 'other',
        'counter' => 20,
    ]);

    $result = InputContractPost::filter(['shape_filter' => 'page'])->get();

    expect($filter->getValue())->toBe(['page'])
        ->and($result->pluck('id')->all())->toBe([$matching->id]);
});

it('accepts an integer scalar multi-select shorthand through scopeFilter', function () {
    $filter = (new SelectFilter('counter'))
        ->setOptions(['Ten' => 10, 'Twenty' => 20])
        ->setMode(FilterMode::CONTAINS)
        ->setQueryName('shape_filter');
    InputContractPost::$configuredFilters = [$filter];
    $matching = InputContractPost::query()->create([
        'title' => 'matching',
        'type' => 'page',
        'published' => true,
        'content' => 'matching',
        'counter' => 10,
    ]);
    InputContractPost::query()->create([
        'title' => 'other',
        'type' => 'post',
        'published' => false,
        'content' => 'other',
        'counter' => 20,
    ]);

    $result = InputContractPost::filter(['shape_filter' => 10])->get();

    expect($filter->getValue())->toBeArray()->toHaveCount(1)
        ->and($filter->getValue()[0])->toEqual(10)
        ->and($result->pluck('id')->all())->toBe([$matching->id]);
});

it('reindexes gapped multi-select scope values before validation', function () {
    $filter = InputContractFilterFactory::make('select_multi')->setQueryName('shape_filter');
    InputContractPost::$configuredFilters = [$filter];
    $matching = InputContractPost::query()->create([
        'title' => 'matching',
        'type' => 'page',
        'published' => true,
        'content' => 'matching',
        'counter' => 10,
    ]);
    InputContractPost::query()->create([
        'title' => 'other',
        'type' => 'post',
        'published' => false,
        'content' => 'other',
        'counter' => 20,
    ]);

    $result = InputContractPost::filter(['shape_filter' => [2 => 'page']])->get();

    expect($filter->getValue())->toBe(['page'])
        ->and($result->pluck('id')->all())->toBe([$matching->id]);
});

it('treats null and empty scalar scope values as absent even in throw mode', function (mixed $input) {
    InputContractPost::$configuredFilters = [
        InputContractFilterFactory::make('string')
            ->setQueryName('shape_filter')
            ->setValidationMode(ValidationMode::THROW),
    ];
    $query = InputContractPost::query();
    $before = InputContractTestSupport::snapshot($query);

    $query->filter(['shape_filter' => $input]);

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with([
    'null' => null,
    'empty string' => '',
]);

it('preserves programmatic boolean false and true values through scopeFilter', function (
    bool $input,
    string $expectedTitle
) {
    InputContractPost::$configuredFilters = [
        InputContractFilterFactory::make('boolean')->setQueryName('shape_filter'),
    ];
    InputContractPost::query()->create([
        'title' => 'true',
        'type' => 'page',
        'published' => true,
        'content' => 'true',
        'counter' => 1,
    ]);
    InputContractPost::query()->create([
        'title' => 'false',
        'type' => 'page',
        'published' => false,
        'content' => 'false',
        'counter' => 2,
    ]);

    expect(InputContractPost::filter(['shape_filter' => $input])->pluck('title')->all())
        ->toBe([$expectedTitle]);
})->with([
    'true' => [true, 'true'],
    'false' => [false, 'false'],
]);

it('preserves the existing handling of unknown trashed values', function (
    ValidationMode $validationMode
) {
    InputContractSoftDeletePost::$configuredFilters = [
        InputContractFilterFactory::make('trashed')
            ->setQueryName('shape_filter')
            ->setValidationMode($validationMode),
    ];
    $query = InputContractSoftDeletePost::query();
    $before = InputContractTestSupport::snapshot($query);

    $query->filter(['shape_filter' => 'unknown']);

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with('scope input validation modes');

it('rejects realistic nested query-string shapes without changing results or SQL', function () {
    $first = Post::factory()->create(['title' => 'needle first']);
    $second = Post::factory()->create(['title' => 'second']);
    $tag = Tag::factory()->create();
    $first->tags()->attach($tag);

    $request = Request::create(
        '/posts'
        .'?contains%5B%5D=needle'
        .'&boolfilter%5Bpublished%5D%5B%5D=1'
        .'&counter_between%5B0%5D%5B%5D=10'
        .'&counter_between%5B1%5D=20'
        .'&created_at_between%5B0%5D%5B%5D=2026-01-01'
        .'&created_at_between%5B1%5D=2026-12-31'
        .'&type_multi%5B0%5D%5B%5D=page'
        .'&tag_filter_contains%5B0%5D%5B%5D='.$tag->id
        .'&tag_timeframe_filter%5Bmode%5D%5B%5D=current'
        .'&tag_timeframe_filter%5Bvalues%5D='.$tag->id,
        'GET'
    );
    $this->app->instance('request', $request);
    $query = Post::query();
    $before = InputContractTestSupport::snapshot($query);
    $expectedIds = Post::query()->orderBy('id')->pluck('id')->all();

    InputContractTestSupport::withPhpWarningsAsExceptions(fn () => $query->filterByQueryString());

    expect($request->query('contains'))->toBe(['needle'])
        ->and($request->query('boolfilter'))->toBe(['published' => ['1']])
        ->and((clone $query)->orderBy('id')->pluck('id')->all())->toBe($expectedIds)
        ->and(InputContractTestSupport::snapshot($query))->toBe($before);
});

it('throws a validation exception with the query path through scopeFilterByQueryString', function () {
    InputContractPost::$configuredFilters = [
        InputContractFilterFactory::make('string')
            ->setQueryName('shape_filter')
            ->setValidationMode(ValidationMode::THROW),
    ];
    $request = Request::create('/posts?shape_filter%5B%5D=needle', 'GET');
    $this->app->instance('request', $request);
    $query = InputContractPost::query();
    $before = InputContractTestSupport::snapshot($query);

    $exception = InputContractTestSupport::validationException(
        fn () => InputContractTestSupport::withPhpWarningsAsExceptions(
            fn () => $query->filterByQueryString()
        )
    );

    expect(array_keys($exception->errors()))->toContain('shape_filter')
        ->and(InputContractTestSupport::snapshot($query))->toBe($before);
});
