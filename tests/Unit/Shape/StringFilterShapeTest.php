<?php

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Tests\Models\Shape\ShapePost;

it('base_query', function () {
    $q = ShapePost::query();

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['select'],
        'bindings' => [],
    ]);
});

it('applies_like', function () {
    $q = ShapePost::query();

    $filter = (new StringFilter())
        ->field('title');

    $filter->populate('hello');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', 'LIKE'],
        'forbidden' => [' join '],
        'bindings' => ['%hello%'],
    ]);
});

it('applies_equal', function () {
    $q = ShapePost::query();

    $filter = (new StringFilter())
        ->field('title')
        ->setMode(FilterMode::EQUAL);

    $filter->populate('hello');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', '= ?'],
        'forbidden' => ['LIKE', ' join '],
        'bindings' => ['hello'],
    ]);
});

it('applies_starts_with', function () {
    $q = ShapePost::query();

    $filter = (new StringFilter())
        ->field('title')
        ->setMode(FilterMode::STARTS_WITH);

    $filter->populate('hello');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', 'LIKE'],
        'bindings' => ['hello%'],
    ]);
});

it('applies_ends_with', function () {
    $q = ShapePost::query();

    $filter = (new StringFilter())
        ->field('title')
        ->setMode(FilterMode::ENDS_WITH);

    $filter->populate('hello');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', 'LIKE'],
        'bindings' => ['%hello'],
    ]);
});
