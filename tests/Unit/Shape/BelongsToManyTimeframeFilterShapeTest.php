<?php

use Lacodix\LaravelModelFilter\Filters\BelongsToManyTimeframeFilter;
use Tests\Models\Shape\ShapePostWithPivot;
use Tests\Models\Shape\ShapeTag;

function makeTimeframeFilter(): BelongsToManyTimeframeFilter
{
    return new class extends BelongsToManyTimeframeFilter {
        protected string $startField = 'start';
        protected string $endField = 'end';

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
    $q = ShapePostWithPivot::query();

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['select'],
        'bindings' => [],
    ]);
});

it('applies_ever_mode', function () {
    $q = ShapePostWithPivot::query();

    $filter = makeTimeframeFilter();
    $filter->field('tags');
    $filter->populate(['values' => '1', 'mode' => 'ever']);
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

it('applies_current_mode', function () {
    $q = ShapePostWithPivot::query();

    $filter = makeTimeframeFilter();
    $filter->field('tags');
    $filter->populate(['values' => '1', 'mode' => 'current']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['exists', 'join', 'shape_tags', 'shape_post_tag', '"shape_post_tag"."start"', '"shape_post_tag"."end"'],
        'forbidden' => [' like '],
        'expectedJoins' => 1,
        'enforceExactJoinCount' => true,
    ]);
});

it('does_not_add_unexpected_joins', function () {
    $q = ShapePostWithPivot::query();

    $filter = makeTimeframeFilter();
    $filter->field('tags');
    $filter->populate(['values' => '1', 'mode' => 'ever']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'expectedJoins' => 1,
        'enforceExactJoinCount' => true,
    ]);
});

it('applies_never_mode_without_values', function () {
    $q = ShapePostWithPivot::query();

    $filter = makeTimeframeFilter();
    $filter->field('tags');
    $filter->populate(['values' => [], 'mode' => 'never']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['not exists', 'shape_post_tag'],
        'forbidden' => ['in (?)'],
        'bindings' => [],
    ]);
});

it('applies_never_mode_with_values', function () {
    $q = ShapePostWithPivot::query();

    $filter = makeTimeframeFilter();
    $filter->field('tags');
    $filter->populate(['values' => '1', 'mode' => 'never']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['not exists', 'shape_tags', 'shape_post_tag'],
        'bindings' => ['1'],
    ]);
});

it('applies_not_current_mode_with_values', function () {
    $q = ShapePostWithPivot::query();

    $filter = makeTimeframeFilter();
    $filter->field('tags');
    $filter->populate(['values' => '1', 'mode' => 'not_current']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['not exists', 'shape_tags', 'shape_post_tag', '"shape_post_tag"."start"', '"shape_post_tag"."end"'],
    ]);
});

it('applies_not_current_mode_without_values', function () {
    $q = ShapePostWithPivot::query();

    $filter = makeTimeframeFilter();
    $filter->field('tags');
    $filter->populate(['values' => [], 'mode' => 'not_current']);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['not exists', 'shape_post_tag', '"shape_post_tag"."start"', '"shape_post_tag"."end"'],
        'forbidden' => ['in (?)'],
    ]);
});
