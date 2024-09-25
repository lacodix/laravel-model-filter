<?php

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\SearchMode;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Tests\Models\Comment;
use Tests\Models\Post;

beforeEach(function () {
    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'title' => 'no '.$this->faker->words(4, true).' test',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'title' => 'no '.$this->faker->words(2, true).' test '.$this->faker->words(2, true).' no',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'title' => 'not to be found',
            'content' => 'also not to be found'
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'title' => 'test '.$this->faker->words(4, true).' no',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'content' => 'START '.$this->faker->words(4, true).' no',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'title' => 'test',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'content' => 'no '.$this->faker->words(4, true).' test',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'content' => 'no '.$this->faker->words(4, true).' THEEND',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'content' => 'no '.$this->faker->words(2, true).' test fake foobar '.$this->faker->words(2, true).' no',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'content' => 'no '.$this->faker->words(2, true).' TEST fake FOOBAR '.$this->faker->words(2, true).' no',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'content' => 'test '.$this->faker->words(4, true).' no',
        ])
        ->create();

    Comment::factory()
        ->for(Post::factory(['title' => 'd-o-e-s-n-o-t-m-a-t-t-e-r', 'content' => 'a-l-s-o-n-o-t']))
        ->state([
            'content' => 'test',
        ])
        ->create();
});

it('can be found by search for multiple value', function () {
    expect(SearchableParameterCommentPost::search('test')->count())->toEqual(9);
});

it('can be found by search for overwritten fields', function () {
    expect(SearchableParameterCommentPost::search('test', ['comments.title'])->count())->toEqual(4);
});

it('can be found by search for overwritten modes', function () {
    expect(SearchableParameterCommentPost::search('test', [
        'comments.title' => SearchMode::EQUAL,
        'comments.content' => SearchMode::LIKE
    ])
        ->count())->toEqual(6)
        ->and(SearchableParameterCommentPost::search('test', [
            'comments.title' => SearchMode::EQUAL,
            'comments.content' => SearchMode::EQUAL
        ])
        ->count())->toEqual(2)
        ->and(SearchableParameterCommentPost::search('test', [
            'comments.title' => SearchMode::STARTS_WITH,
            'comments.content' => SearchMode::ENDS_WITH
        ])->count())->toEqual(4);
});

it('cannot search for unknown overwritten values', function () {
    expect(SearchableParameterCommentPost::search('page', [
        'type' => SearchMode::EQUAL,
    ])
        ->count())->toEqual(12);
});

it('can be found by method search for overwritten fields', function () {
    expect(SearchableMethodCommentPost::search('test', ['comments.title'])->count())->toEqual(4);
});

it('can be found by case insensitive search for multiple value', function () {
    expect(SearchableParameterCommentPost::search('TEST')->count())->toEqual(9);
});

it('can be found by case sensitive search for multiple value', function () {
    expect(SearchableParameterCommentPost::search('TEST', [
        'comments.content' => SearchMode::LIKE_CASE_SENSITIVE
    ])->count())->toEqual(1)
        ->and(SearchableParameterCommentPost::search('START', [
            'comments.content' => SearchMode::STARTS_WITH_CASE_SENSITIVE
        ])->count())->toEqual(1)
        ->and(SearchableParameterCommentPost::search('THEEND', [
            'comments.content' => SearchMode::ENDS_WITH_CASE_SENSITIVE
        ])->count())->toEqual(1)
        ->and(SearchableParameterCommentPost::search('test foobar start theend', [
            'comments.content' => SearchMode::CONTAINS_ANY
        ])->count())->toEqual(7)
        ->and(SearchableParameterCommentPost::search('THEEND FOOBAR', [
            'comments.content' => SearchMode::CONTAINS_ANY_CASE_SENSITIVE
        ])->count())->toEqual(2)
        ->and(SearchableParameterCommentPost::search('test foobar', [
            'comments.content' => SearchMode::CONTAINS_ALL
        ])->count())->toEqual(2)
        ->and(SearchableParameterCommentPost::search('test foobar', [
            'comments.content' => SearchMode::CONTAINS_ALL_CASE_SENSITIVE
        ])->count())->toEqual(1)
    ;
});

class SearchableParameterCommentPost extends Model
{
    use IsSearchable;

    protected $table = 'posts';

    protected array $searchable = [
        'title',
        'content',
        'comments.title',
        'comments.content',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}

class SearchableMethodCommentPost extends Model
{
    use IsSearchable;

    protected $table = 'posts';
    protected $guarded = [];

    public function searchable(): array
    {
        return [
            'title',
            'content',
            'comments.title',
            'comments.content',
        ];
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
