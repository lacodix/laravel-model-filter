<?php

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\BelongsToManyFilter;
use Tests\Models\Shape\ShapePost;
use Tests\Models\Shape\ShapeTag;

function makeBelongsToManyFilter(): BelongsToManyFilter
{
    return new class extends BelongsToManyFilter {
        public function options(): array
        {
            return ['Tag1' => '1', 'Tag2' => '2'];
        }

        public function relationQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return ShapeTag::query();
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

    $filter = makeBelongsToManyFilter();
    $filter->field('tags');
    $filter->populate('1');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['exists', 'join', 'shape_tags', 'shape_post_tag'],
        'forbidden' => [' like '],
        'bindings' => ['1'],
        'expectedJoins' => 1,
        'enforceExactJoinCount' => true,
    ]);
});

it('applies_contains', function () {
    $q = ShapePost::query();

    $filter = makeBelongsToManyFilter();
    $filter->field('tags')->setMode(FilterMode::CONTAINS);
    $filter->populate(['1', '2']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['exists', 'join', 'shape_tags', 'shape_post_tag', 'in'],
        'forbidden' => [' like '],
        'bindings' => ['1', '2'],
        'expectedJoins' => 1,
        'enforceExactJoinCount' => true,
    ]);
});

it('does_not_add_unexpected_joins', function () {
    $q = ShapePost::query();

    $filter = makeBelongsToManyFilter();
    $filter->field('tags');
    $filter->populate('1');
    $filter->apply($q);

    // Only 1 join inside the exists subquery, no joins on the main query
    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'expectedJoins' => 1,
        'enforceExactJoinCount' => true,
    ]);
});
