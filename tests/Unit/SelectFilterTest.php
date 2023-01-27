<?php

use Illuminate\Validation\ValidationException;
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
});

it('can be filtered by select', function () {
    expect(Post::filter(['type_filter' => 'page'])->count())->toEqual(15)
        ->and(Post::filter(['type_filter' => 'post'])->count())->toEqual(10);
});

it('is doesn\'t apply if single value is invalid', function () {
    expect(Post::filter([
        'type_filter' => 'asdf',
    ])->count())->toEqual(25);
});

it('is invalid with not allowed value', function () {
    Post::filter([
        'type_filter_throws' => 'asdf',
    ]);
})->throws(ValidationException::class);
