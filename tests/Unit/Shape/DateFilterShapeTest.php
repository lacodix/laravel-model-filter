<?php

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Tests\Models\Shape\ShapePost;

it('base_query', function () {
    $q = ShapePost::query();

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['select'],
        'bindings' => [],
    ]);
});

it('applies_equal', function () {
    $q = ShapePost::query();

    $filter = (new DateFilter())
        ->field('created_at');

    $filter->populate('2023-01-01');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', 'created_at', 'cast(? as text)'],
        'forbidden' => [' like '],
        'bindings' => ['2023-01-01'],
    ]);
});

it('applies_greater_or_equal', function () {
    $q = ShapePost::query();

    $filter = (new DateFilter())
        ->field('created_at')
        ->setMode(FilterMode::GREATER_OR_EQUAL);

    $filter->populate('2023-01-01');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', 'created_at', '>= cast(? as text)'],
        'forbidden' => [' like '],
        'bindings' => ['2023-01-01'],
    ]);
});

it('applies_between', function () {
    $q = ShapePost::query();

    $filter = (new DateFilter())
        ->field('created_at')
        ->setMode(FilterMode::BETWEEN);

    $filter->populate(['2023-01-01', '2023-12-31']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', 'created_at', '>= cast(? as text)', '<= cast(? as text)'],
        'forbidden' => [' like ', ' join '],
        'bindings' => ['2023-01-01', '2023-12-31'],
    ]);
});

it('handles_empty_value', function () {
    $q = ShapePost::query();

    $filter = (new DateFilter())
        ->field('created_at');

    $filter->populate('');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', 'created_at'],
        'bindings' => [''],
    ]);
});
