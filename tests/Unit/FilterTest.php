<?php

use Carbon\Carbon;
use function Pest\Faker\faker;
use Tests\Models\Post;

it('can be filtered by date', function () {
    Post::factory()
        ->state([
            'created_at' => faker()->dateTimeBetween('-10 week', '-1 week'),
        ])
        ->count(15)
        ->create();

    Post::factory()
        ->state([
            'created_at' => faker()->dateTimeBetween('+1 week', '+10 week'),
        ])
        ->count(10)
        ->create();

    expect(Post::filter(['created_at_lower_filter' => Carbon::now()])->count())->toEqual(15)
        ->and(Post::filter(['created_at_greater_filter' => Carbon::now()])->count())->toEqual(10);
});

it('can be filtered by select', function () {
    Post::factory()
        ->state([
            'type' => 'page',
        ])
        ->count(15)
        ->create();

    Post::factory()
        ->state([
            'type' => 'post',
        ])
        ->count(10)
        ->create();

    expect(Post::filter(['type_filter' => 'page'])->count())->toEqual(15)
        ->and(Post::filter(['type_filter' => 'post'])->count())->toEqual(10);
});

it('can be filtered by string', function () {
    Post::factory()
        ->state([
            'title' => 'no '.faker()->words(4, true).' test',
        ])
        ->count(5)
        ->create();

    Post::factory()
        ->state([
            'title' => 'no '.faker()->words(2, true).' test '.faker()->words(2, true).' no',
        ])
        ->count(5)
        ->create();

    Post::factory()
        ->state([
            'title' => 'test '.faker()->words(4, true).' no',
        ])
        ->count(10)
        ->create();

    Post::factory()
        ->state([
            'title' => 'test',
        ])
        ->count(1)
        ->create();

    expect(Post::filter(['starts_with' => 'test'])->count())->toEqual(11)
        ->and(Post::filter(['ends_with' => 'test'])->count())->toEqual(6)
        ->and(Post::filter(['contains' => 'test'])->count())->toEqual(21)
        ->and(Post::filter(['equals' => 'test'])->count())->toEqual(1);
});

it('can be filtered by boolean', function () {
    Post::factory()
        ->state([
            'published' => false,
        ])
        ->count(15)
        ->create();

    Post::factory()
        ->state([
            'published' => true,
        ])
        ->count(10)
        ->create();

    expect(Post::filter(['boolfilter' => ['published' => true]])->count())->toEqual(10)
        ->and(Post::filter(['boolfilter' => ['published' => false]])->count())->toEqual(15);
});

it('can be filtered individually', function () {
    Post::factory()
        ->count(15)
        ->create();

    Post::factory()
        ->state([
            'title' => 'test1',
            'content' => 'test2',
            'type' => 'page',
            'published' => true,
        ])
        ->count(1)
        ->create();

    expect(Post::filter(['individual_filter' => 'no matter'])->count())->toEqual(1);
});
