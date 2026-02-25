<?php

use Lacodix\LaravelModelFilter\Filters\BelongsToFilter;
use Tests\Models\Shape\ShapePost;

function makeBelongsToFilter(): BelongsToFilter
{
    return new class extends BelongsToFilter {
        public function options(): array
        {
            return ['Tag1' => '1', 'Tag2' => '2'];
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

it('applies_with_valid_value', function () {
    $q = ShapePost::query();

    $filter = makeBelongsToFilter();
    $filter->field('shape_tag_id');
    $filter->populate('1');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"shape_tag_id"', '= ?'],
        'forbidden' => [' like ', ' join ', 'exists'],
        'bindings' => ['1'],
    ]);
});

it('handles_invalid_value', function () {
    $q = ShapePost::query();

    $filter = makeBelongsToFilter();
    $filter->field('shape_tag_id');
    $filter->populate('999');
    $filter->apply($q);

    // 999 not in options() → no where added
    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'forbidden' => ['where'],
        'bindings' => [],
    ]);
});
