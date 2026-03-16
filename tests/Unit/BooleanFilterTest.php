<?php

use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Tests\Models\Post;

it('can filter by boolean true', function () {
    $q = Post::query();

    $filter = new BooleanFilter('published');
    $filter->populate('1');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'posts',
        'required' => ['where', '"published"', '= ?'],
        'bindings' => [true],
    ]);
});

it('can filter by boolean false', function () {
    $q = Post::query();

    $filter = new BooleanFilter('published');
    $filter->populate('0');
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'posts',
        'required' => ['where', '"published"', '= ?'],
        'bindings' => [false],
    ]);
});

it('does not filter if value is null', function () {
    $q = Post::query();

    $filter = new BooleanFilter('published');
    $filter->populate(null);
    $filter->apply($q);

    expect($q)->toHaveSqlShape([
        'from' => 'posts',
        'forbidden' => ['where'],
    ]);
});
