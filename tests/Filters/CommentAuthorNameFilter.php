<?php

namespace Tests\Filters;

use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Traits\RunsOnRelation;

class CommentAuthorNameFilter extends StringFilter
{
    use RunsOnRelation;

    protected string $queryName = 'comment_author_name_filter';
    protected string $relation = 'post';
    protected string $field = 'title';
}
