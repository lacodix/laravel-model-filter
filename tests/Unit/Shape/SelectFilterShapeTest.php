<?php

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Tests\Models\Shape\ShapePost;

function makeSelectFilter(): SelectFilter
{
    return new class extends SelectFilter {
        public function options(): array
        {
            return ['Draft' => 'draft', 'Published' => 'published'];
        }
    };
}

it('base_query', function () {
    $q = ShapePost::query();

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['select'],
        'bindings' => [],
    ]);
});

it('applies_default_equal', function () {
    $q = ShapePost::query();

    $filter = makeSelectFilter();
    $filter->field('title');
    $filter->populate('draft');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', '= ?'],
        'forbidden' => [' like ', ' join ', ' in '],
        'bindings' => ['draft'],
    ]);
});

it('applies_contains', function () {
    $q = ShapePost::query();

    $filter = makeSelectFilter();
    $filter->field('title')->setMode(FilterMode::CONTAINS);
    $filter->populate(['draft', 'published']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', 'in'],
        'forbidden' => [' like ', ' join '],
        'bindings' => ['draft', 'published'],
    ]);
});

it('handles_invalid_option', function () {
    $q = ShapePost::query();

    $filter = makeSelectFilter();
    $filter->field('title');
    $filter->populate('invalid');
    $filter->apply($q);

    // invalid option not in options() → when() is false, no where added
    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'forbidden' => ['where'],
        'bindings' => [],
    ]);
});
