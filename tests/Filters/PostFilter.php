<?php

namespace Tests\Filters;

use Lacodix\LaravelModelFilter\Filters\BelongsToFilter;

class PostFilter extends BelongsToFilter
{
    protected string $field = 'post_id';

    protected string $relationModel = \Tests\Models\Post::class;
    protected string $titleColumn = 'title';
}
