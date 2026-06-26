<?php

declare(strict_types=1);

namespace Tests\Filters;

use Lacodix\LaravelModelFilter\Filters\BelongsToManyFilter;
use Tests\Models\Tag;

class TagFilter extends BelongsToManyFilter
{
    protected string $field = 'tags';

    protected string $relationModel = Tag::class;
    protected string $titleColumn = 'title';
}
