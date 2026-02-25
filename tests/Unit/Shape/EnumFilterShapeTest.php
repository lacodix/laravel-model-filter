<?php

use Lacodix\LaravelModelFilter\Filters\EnumFilter;
use Tests\Models\Shape\ShapePost;

enum ShapeTestStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}

function makeEnumFilter(): EnumFilter
{
    $filter = new class extends EnumFilter {
        protected string $enum = 'ShapeTestStatus';
    };

    return $filter;
}

it('base_query', function () {
    $q = ShapePost::query();

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['select'],
        'bindings' => [],
    ]);
});

it('applies_with_valid_enum_value', function () {
    $q = ShapePost::query();

    $filter = makeEnumFilter();
    $filter->field('title');
    $filter->populate('draft');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'required' => ['where', '"title"', '= ?'],
        'forbidden' => [' like ', ' join '],
        'bindings' => ['draft'],
    ]);
});

it('handles_invalid_enum_value', function () {
    $q = ShapePost::query();

    $filter = makeEnumFilter();
    $filter->field('title');
    $filter->populate('invalid');
    $filter->apply($q);

    // invalid not in options() → no where added
    expect($q)->toHaveSqlShape([
        'from' => 'shape_posts',
        'forbidden' => ['where'],
        'bindings' => [],
    ]);
});
