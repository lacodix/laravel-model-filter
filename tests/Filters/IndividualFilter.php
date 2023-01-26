<?php

namespace Tests\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Filters\Filter;

class IndividualFilter extends Filter
{
    public function apply(Builder $query): Builder
    {
        return $query
            ->where('title', 'test1')
            ->where('content', 'test2')
            ->where('type', 'page')
            ->where('published', true);
    }
}
