<?php

use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Tests\Models\Shape\ShapePost;

it('base_query', function () {
    $q = ShapePost::query();

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['select'],
        'bindings' => [],
    ]);
});

it('applies_with_true_value', function () {
    $q = ShapePost::query();

    $filter = new BooleanFilter(['title']);
    $filter->populate(['title' => '1']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', '= ?'],
        'forbidden' => [' like ', ' join '],
        'bindings' => [true],
    ]);
});

it('applies_with_false_value', function () {
    $q = ShapePost::query();

    $filter = new BooleanFilter(['title']);
    $filter->populate(['title' => '0']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', '= ?'],
        'forbidden' => [' like ', ' join '],
        'bindings' => [false],
    ]);
});

it('handles_null_value', function () {
    $q = ShapePost::query();

    $filter = new BooleanFilter(['title']);
    $filter->populate(['title' => null]);
    $filter->apply($q);

    // null value means the when() condition is false, no where added
    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'forbidden' => ['where'],
        'bindings' => [],
    ]);
});
