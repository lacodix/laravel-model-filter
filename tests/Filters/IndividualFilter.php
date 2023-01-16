<?php

namespace Tests\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelFilter\Filters\Filter;
use Lacodix\LaravelFilter\Filters\SelectFilter;

class IndividualFilter extends Filter
{
    public function apply(Builder $query, array|string $values): Builder
    {
        return $query
            ->where('title', 'test1')
            ->where('content', 'test2')
            ->where('type', 'page')
            ->where('published', true);
    }
}