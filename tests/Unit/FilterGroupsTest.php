<?php

use Tests\Models\Comment;

beforeEach(function () {
    Comment::factory()
        ->state([
            'published' => false,
            'counter' => $this->faker->numberBetween(0, 5000),
        ])
        ->count(7)
        ->create();

    Comment::factory()
        ->state([
            'published' => true,
            'counter' => $this->faker->numberBetween(0, 5000),
        ])
        ->count(5)
        ->create();

    Comment::factory()
        ->state([
            'published' => false,
            'counter' => $this->faker->numberBetween(6000, 10000),
        ])
        ->count(7)
        ->create();

    Comment::factory()
        ->state([
            'published' => true,
            'counter' => $this->faker->numberBetween(6000, 10000),
        ])
        ->count(5)
        ->create();

    Comment::factory()
        ->state([
            'published' => false,
            'counter' => $this->faker->numberBetween(12000, 20000),
        ])
        ->count(7)
        ->create();

    Comment::factory()
        ->state([
            'published' => true,
            'counter' => $this->faker->numberBetween(12000, 20000),
        ])
        ->count(5)
        ->create();
});

it('can be filtered by group', function () {
    expect(Comment::filter(['published_filter' => ['published' => true]], 'frontend')->count())->toEqual(15)
        ->and(Comment::filter(['published_filter' => ['published' => false]], 'frontend')->count())->toEqual(21)
        ->and(Comment::filter(['counter_filter' => 5500], 'backend')->count())->toEqual(12)
        ->and(Comment::filter(['counter_filter' => 10000], 'backend')->count())->toEqual(24);
});

it('is not filtered with false group', function () {
    expect(Comment::filter(['published_filter' => ['published' => true]], 'backend')->count())->toEqual(36)
        ->and(Comment::filter(['published_filter' => ['published' => false]], 'backend')->count())->toEqual(36)
        ->and(Comment::filter(['counter_filter' => 5500], 'frontend')->count())->toEqual(36)
        ->and(Comment::filter(['counter_filter' => 10000], 'frontend')->count())->toEqual(36);
});
