<?php

use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Tests\Filters\TypeFilter;
use Tests\Models\Post;

beforeEach(function () {
    Post::factory()->state(['type' => 'page'])->count(15)->create();
    Post::factory()->state(['type' => 'post'])->count(10)->create();
    Post::factory()->state(['type' => null])->count(5)->create();
});

it('filters records where the field is null', function () {
    expect(Post::filter(['type_nullable' => '__null__'])->count())->toEqual(5);
});

it('still filters by regular values when nullable is enabled', function () {
    expect(Post::filter(['type_nullable' => 'page'])->count())->toEqual(15)
        ->and(Post::filter(['type_nullable' => 'post'])->count())->toEqual(10);
});

it('does not treat the null sentinel specially when filter is not nullable', function () {
    // type_filter is not nullable, the sentinel is invalid and therefore skipped
    expect(Post::filter(['type_filter' => '__null__'])->count())->toEqual(30);
});

it('filters null values in contains mode', function () {
    expect(Post::filter(['type_nullable_multi' => ['__null__']])->count())->toEqual(5)
        ->and(Post::filter(['type_nullable_multi' => ['page', '__null__']])->count())->toEqual(20)
        ->and(Post::filter(['type_nullable_multi' => ['page']])->count())->toEqual(15);
});

it('excludes null values in not_contains mode', function () {
    expect(Post::filter(['type_nullable_not_contains' => ['__null__']])->count())->toEqual(25)
        ->and(Post::filter(['type_nullable_not_contains' => ['page', '__null__']])->count())->toEqual(10);
});

it('adds the null option to optionsWithNull only', function () {
    $filter = (new TypeFilter)->nullable();

    expect($filter->isNullable())->toBeTrue()
        ->and($filter->options())->toBe(['page', 'post'])
        ->and($filter->optionsWithNull())->toBe(['None' => '__null__', 'page', 'post']);
});

it('does not expose the null option without nullable', function () {
    $filter = new TypeFilter;

    expect($filter->isNullable())->toBeFalse()
        ->and($filter->optionsWithNull())->toBe(['page', 'post']);
});

it('allows a custom null label', function () {
    $filter = (new TypeFilter)->nullable(label: 'No payment method');

    expect($filter->optionsWithNull())->toBe(['No payment method' => '__null__', 'page', 'post']);
});

it('is itself a select filter', function () {
    expect(new TypeFilter)->toBeInstanceOf(SelectFilter::class);
});
