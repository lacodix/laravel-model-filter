<?php

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\SearchMode;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
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
            'title' => 'not to be found',
            'content' => 'also not to be found'
        ])
        ->create();

    Post::factory()
        ->state([
            'title' => 'test '.$this->faker->words(4, true).' no',
        ])
        ->create();

    Post::factory()
        ->state([
            'content' => 'START '.$this->faker->words(4, true).' no',
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
            'content' => 'no '.$this->faker->words(4, true).' THEEND',
        ])
        ->create();

    Post::factory()
        ->state([
            'content' => 'no '.$this->faker->words(2, true).' test fake foobar '.$this->faker->words(2, true).' no',
        ])
        ->create();

    Post::factory()
        ->state([
            'content' => 'no '.$this->faker->words(2, true).' TEST fake FOOBAR '.$this->faker->words(2, true).' no',
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
    expect(Post::search('test')->count())->toEqual(9);
});

it('can be found by search for overwritten fields', function () {
    expect(Post::search('test', ['title'])->count())->toEqual(4);
});

it('can be found by search for overwritten modes', function () {
    expect(Post::search('test', [
        'title' => SearchMode::EQUAL,
        'content' => SearchMode::LIKE
    ])
        ->count())->toEqual(6)
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
        ->count())->toEqual(12);
});

it('can be found by method search for overwritten fields', function () {
    expect(SearchableMethodPost::search('test', ['title'])->count())->toEqual(4);
});

it('can be found by case insensitive search for multiple value', function () {
    expect(Post::search('TEST')->count())->toEqual(9);
});

it('can be found by case sensitive search for multiple value', function () {
    expect(Post::search('TEST', [
        'content' => SearchMode::LIKE_CASE_SENSITIVE
    ])->count())->toEqual(1)
        ->and(Post::search('START', [
            'content' => SearchMode::STARTS_WITH_CASE_SENSITIVE
        ])->count())->toEqual(1)
        ->and(Post::search('THEEND', [
            'content' => SearchMode::ENDS_WITH_CASE_SENSITIVE
        ])->count())->toEqual(1)
        ->and(Post::search('test foobar start theend', [
            'content' => SearchMode::CONTAINS_ANY
        ])->count())->toEqual(7)
        ->and(Post::search('THEEND FOOBAR', [
            'content' => SearchMode::CONTAINS_ANY_CASE_SENSITIVE
        ])->count())->toEqual(2)
        ->and(Post::search('test foobar', [
            'content' => SearchMode::CONTAINS_ALL
        ])->count())->toEqual(2)
        ->and(Post::search('test foobar', [
            'content' => SearchMode::CONTAINS_ALL_CASE_SENSITIVE
        ])->count())->toEqual(1)
    ;
});


class SearchableMethodPost extends Model
{
    use IsSearchable;

    protected $table = 'posts';
    protected $guarded = [];

    public function searchable(): array
    {
        return [
            'title',
            'content',
        ];
    }
}
