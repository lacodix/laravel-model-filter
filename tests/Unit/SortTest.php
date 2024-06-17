<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\IsSortable;
use Tests\Models\Post;

beforeEach(function () {
    Post::factory()
        ->state([
            'title' => 'b filler the first',
            'counter' => 50,
            'type' => 'post',
            'created_at' => Carbon::now()->subDay(),
        ])
        ->create();

    Post::factory()
        ->state([
            'title' => 'b filler '.$this->faker->words(2, true),
            'counter' => 50,
            'type' => 'post',
            'created_at' => Carbon::now()->subDay(),
        ])
        ->count(10)
        ->create();

    Post::factory()
        ->state([
            'title' => 'a the first',
            'counter' => 100,
            'type' => 'post',
            'created_at' => Carbon::now(),
        ])
        ->create();

    Post::factory()
        ->state([
            'title' => 'c the middle',
            'counter' => 1,
            'type' => 'post',
            'created_at' => Carbon::now(),
        ])
        ->create();

    Post::factory()
        ->state([
            'title' => 'd the end',
            'counter' => 50,
            'type' => 'page',
            'created_at' => Carbon::now()->subWeek(),
        ])
        ->create();
});

it('is first when sorting by title', function () {
    expect(Post::sort(['title' => 'asc'])->first())->title->toEqual('a the first')
        ->and(Post::sort(['title' => 'desc'])->first())->title->toEqual('d the end');
});

it('is first when sorting by created_at', function () {
    expect(Post::sort(['created_at' => 'asc'])->first())->title->toEqual('d the end')
        ->and(Post::sort(['created_at' => 'desc'])->first())->title->toEqual('a the first');
});

it('is first when sorting by counter', function () {
    expect(Post::sort(['counter' => 'asc'])->first())->title->toEqual('c the middle')
        ->and(Post::sort(['counter' => 'desc'])->first())->title->toEqual('a the first');
});

it('cannot sort for unknown sortables', function () {
    expect(Post::sort(['type' => 'asc'])->first())->title->toEqual('b filler the first');
});

it('is first when sorting by title with method', function () {
    expect(SortableMethodPost::sort(['title' => 'asc'])->first())->title->toEqual('a the first')
        ->and(SortableMethodPost::sort(['title' => 'desc'])->first())->title->toEqual('d the end');
});

class SortableMethodPost extends Model
{
    use IsSortable;

    protected $table = 'posts';
    protected $guarded = [];

    public function sortable(): array
    {
        return [
            'title',
            'created_at',
            'counter',
        ];
    }
}
