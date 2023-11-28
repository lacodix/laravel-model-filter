<?php

use Tests\Models\Post;

beforeEach(function () {
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

    Post::factory()
        ->state([
            'type' => 'someother',
        ])
        ->count(5)
        ->create();
});

it('can be filtered by select', function () {
    expect(Post::filter(['type_multi' => ['post', 'page']])->count())->toEqual(25);
});

it('doesn\'t apply if invalid values are there', function () {
    expect(Post::filter([
        'type_multi' => ['post', 'asdf'],
    ])->count())->toEqual(30);
});
