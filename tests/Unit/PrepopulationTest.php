<?php

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Filters\Traits\Prepopulation;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Tests\Models\Post;

beforeEach(function () {
    Post::factory(['type' => 'post'])->create();
    Post::factory(['type' => 'page'])->count(2)->create();
    Post::factory(['type' => 'comment'])->count(3)->create();
    Post::factory(['type' => 'image'])->create();
});

it('it prepopulates the filters options', function () {
    expect((new TestPost)->filterInstances()->first()->options())->toHaveCount(4)
        ->toEqual(['post', 'page', 'comment', 'image']);
});

class TestTypeFilter extends SelectFilter
{
    use Prepopulation;

    protected string $field = 'type';
}

class TestPost extends Model
{
    use HasFilters;

    protected $table = 'posts';

    protected array $filters = [
        TestTypeFilter::class,
    ];
}
