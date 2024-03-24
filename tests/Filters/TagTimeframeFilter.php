<?php

namespace Tests\Filters;

use Lacodix\LaravelModelFilter\Filters\BelongsToManyTimeframeFilter;
use Tests\Models\Tag;

class TagTimeframeFilter extends BelongsToManyTimeframeFilter
{
    protected string $field = 'tags';
    protected string $startField = 'start';
    protected string $endField = 'end';

    protected string $relationModel = Tag::class;
    protected string $titleColumn = 'title';
}
