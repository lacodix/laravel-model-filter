<?php

use Lacodix\LaravelModelFilter\Filters\TrashedFilter;
use Tests\Models\Shape\ShapePostSoftDelete;

it('base_query', function () {
    $q = ShapePostSoftDelete::query();

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', 'deleted_at', 'is null'],
        'bindings' => [],
    ]);
});

it('applies_with_trashed', function () {
    $q = ShapePostSoftDelete::query();

    $filter = new TrashedFilter();
    $filter->populate('with_trashed');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'forbidden' => ['deleted_at'],
        'bindings' => [],
    ]);
});

it('applies_only_trashed', function () {
    $q = ShapePostSoftDelete::query();

    $filter = new TrashedFilter();
    $filter->populate('only_trashed');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', 'deleted_at', 'is not null'],
        'forbidden' => [' like ', ' join '],
        'bindings' => [],
    ]);
});
