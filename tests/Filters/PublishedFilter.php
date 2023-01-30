<?php

namespace Tests\Filters;

use Lacodix\LaravelModelFilter\Filters\BooleanFilter;

class PublishedFilter extends BooleanFilter
{
    public function options(): array
    {
        return [
            'published',
        ];
    }
}
