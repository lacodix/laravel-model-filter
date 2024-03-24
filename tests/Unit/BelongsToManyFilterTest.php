<?php

use Tests\Models\Post;
use Tests\Models\Tag;

beforeEach(function () {
    $this->tag1 = Tag::factory()
        ->hasAttached(
            Post::factory()
            ->state([
                'type' => 'page',
            ])
            ->count(3)
        )
        ->create();
    $this->tag2 = Tag::factory()
        ->hasAttached(
            Post::factory()
            ->state([
                'type' => 'page',
            ])
            ->count(5)
        )
        ->create();
    $this->tag3 = Tag::factory()
        ->hasAttached(
            Post::factory()
            ->state([
                'type' => 'page',
            ])
            ->count(4)
        )
        ->create();
});

it('can be filtered by select', function () {
    expect(Post::filter(['tag_filter' => $this->tag1->id])->count())->toEqual(3)
        ->and(Post::filter(['tag_filter' => $this->tag2->id])->count())->toEqual(5);
});

it('can be filtered by multiselect', function () {
    expect(Post::filter(['tag_filter_contains' => [$this->tag1->id, $this->tag3->id]])->count())->toEqual(7);
});

it('is doesn\'t apply if single value is invalid', function () {
    expect(Post::filter([
        'tag_filter' => 'asdf',
    ])->count())->toEqual(12);
});

it('contains the correct values', function () {
    expect(array_keys((new \Tests\Filters\TagFilter())->options()))->toHaveCount(3)
        ->toContain($this->tag1->title)
        ->toContain($this->tag2->title)
        ->toContain($this->tag3->title);
});
