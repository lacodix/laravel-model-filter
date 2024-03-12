<?php

use Illuminate\Validation\ValidationException;
use Tests\Models\Comment;
use Tests\Models\Post;

beforeEach(function () {
    $this->post1 = Post::factory()
        ->state([
            'type' => 'page',
        ])
        ->has(Comment::factory()->count(4))
        ->create();
    $this->post2 = Post::factory()
        ->state([
            'type' => 'page',
        ])
        ->has(Comment::factory()->count(8))
        ->create();
    Post::factory()
        ->state([
            'type' => 'page',
        ])
        ->has(Comment::factory()->count(5))
        ->count(3)
        ->create();
});

it('can be filtered by select', function () {
    expect(Comment::filter(['post_filter' => $this->post1->id])->count())->toEqual(4)
        ->and(Comment::filter(['post_filter' => $this->post2->id])->count())->toEqual(8);
});

it('is doesn\'t apply if single value is invalid', function () {
    expect(Comment::filter([
        'post_filter' => 'asdf',
    ])->count())->toEqual(27);
});

it('is invalid with not allowed value', function () {
    Comment::filter([
        'post_filter_throws' => 'asdf',
    ]);
})->throws(ValidationException::class);

it('can be filtered by multi select', function () {
    expect(Comment::filter(['post_filter_multi' => [$this->post1->id, $this->post2->id]])->count())->toEqual(12);
});

it('contains the correct values', function () {
    expect(array_keys((new \Tests\Filters\PostFilter())->options()))->toHaveCount(5)
        ->toContain($this->post1->title)
        ->toContain($this->post2->title);
});
