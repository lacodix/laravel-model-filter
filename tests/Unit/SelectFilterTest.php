<?php

use Illuminate\Validation\ValidationException;
use Tests\Models\Post;

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

it('is invalid with not allowed value', function () {
    Post::filter([
        'type_filter' => 'asdf',
    ]);
})->throws(ValidationException::class);
