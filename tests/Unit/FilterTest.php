<?php

use Tests\Models\Post;

it('can be filtered by string', function () {
    Post::factory()
        ->state([
            'title' => 'no '.$this->faker->words(4, true).' test',
        ])
        ->count(5)
        ->create();

    Post::factory()
        ->state([
            'title' => 'no '.$this->faker->words(2, true).' test '.$this->faker->words(2, true).' no',
        ])
        ->count(5)
        ->create();

    Post::factory()
        ->state([
            'title' => 'test '.$this->faker->words(4, true).' no',
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
