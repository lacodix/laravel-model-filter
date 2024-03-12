<?php

use Tests\Models\Comment;

beforeEach(function () {
    Comment::factory()
        ->state([
            'title' => 'the ' . $this->faker->words(random_int(5, 15), true)
        ])
        ->count(7)
        ->create();

    Comment::factory()
        ->state([
            'title' => 'all the first one'
        ])
        ->create();

    Comment::factory()
        ->state([
            'title' => 'zulu is the greatest'
        ])
        ->create();
});

it('sorts by default sorting', function () {
    expect(Comment::sort()->first())->title->toEqual('zulu is the greatest');
});

it('overwrites default sorting', function () {
    expect(Comment::sort(['title' => 'asc'])->first())->title->toEqual('all the first one');
});

it('overwrites default sorting without given direction', function () {
    expect(Comment::sort(['title'])->first())->title->toEqual('all the first one');
});
