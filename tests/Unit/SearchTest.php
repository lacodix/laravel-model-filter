<?php

use Lacodix\LaravelModelFilter\Enums\SearchMode;
use Tests\Models\Post;

beforeEach(function () {
    Post::factory()
        ->state([
            'title' => 'no '.$this->faker->words(4, true).' test',
        ])
        ->create();

    Post::factory()
        ->state([
            'title' => 'no '.$this->faker->words(2, true).' test '.$this->faker->words(2, true).' no',
        ])
        ->create();

    Post::factory()
        ->state([
            'title' => 'test '.$this->faker->words(4, true).' no',
        ])
        ->create();

    Post::factory()
        ->state([
            'title' => 'test',
        ])
        ->create();

    Post::factory()
        ->state([
            'content' => 'no '.$this->faker->words(4, true).' test',
        ])
        ->create();

    Post::factory()
        ->state([
            'content' => 'no '.$this->faker->words(2, true).' test '.$this->faker->words(2, true).' no',
        ])
        ->create();

    Post::factory()
        ->state([
            'content' => 'test '.$this->faker->words(4, true).' no',
            'type' => 'page',
        ])
        ->create();

    Post::factory()
        ->state([
            'content' => 'test',
            'type' => 'post',
        ])
        ->create();
});

it('can be found by search for multiple value', function () {
    expect(Post::search('test')->count())->toEqual(8);
});

it('can be found by search for overwritten fields', function () {
    expect(Post::search('test', ['title'])->count())->toEqual(4);
});

it('can be found by search for overwritten modes', function () {
    expect(Post::search('test', [
        'title' => SearchMode::EQUAL,
        'content' => SearchMode::LIKE
    ])
        ->count())->toEqual(5)
        ->and(Post::search('test', [
            'title' => SearchMode::EQUAL,
            'content' => SearchMode::EQUAL
        ])
        ->count())->toEqual(2)
        ->and(Post::search('test', [
            'title' => SearchMode::STARTS_WITH,
            'content' => SearchMode::ENDS_WITH
        ])->count())->toEqual(4);
});

it('cannot search for unknown overwritten values', function () {
    expect(Post::search('page', [
        'type' => SearchMode::EQUAL,
    ])
        ->count())->toEqual(8);
});
