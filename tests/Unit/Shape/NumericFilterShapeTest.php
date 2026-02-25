<?php

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;
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

    $filter = (new NumericFilter())
        ->field('id');

    $filter->populate('42');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"id"', '= ?'],
        'forbidden' => [' like ', ' join '],
        'bindings' => ['42'],
    ]);
});

it('applies_greater_or_equal', function () {
    $q = ShapePost::query();

    $filter = (new NumericFilter())
        ->field('id')
        ->setMode(FilterMode::GREATER_OR_EQUAL);

    $filter->populate('10');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"id"', '>= ?'],
        'forbidden' => [' like '],
        'bindings' => ['10'],
    ]);
});

it('applies_between', function () {
    $q = ShapePost::query();

    $filter = (new NumericFilter())
        ->field('id')
        ->setMode(FilterMode::BETWEEN);

    $filter->populate(['10', '20']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"id"', '>= ?', '<= ?'],
        'forbidden' => [' like ', ' join '],
        'bindings' => ['10', '20'],
    ]);
});

it('handles_empty_value', function () {
    $q = ShapePost::query();

    $filter = (new NumericFilter())
        ->field('id');

    $filter->populate('');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"id"'],
        'bindings' => [''],
    ]);
});
