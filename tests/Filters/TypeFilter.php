<?php

namespace Tests\Filters;

use Lacodix\LaravelModelFilter\Filters\SelectFilter;

class TypeFilter extends SelectFilter
{
    protected string $field = 'type';

    public function options(): array
    {
        return [
            'page',
            'post',
        ];
    }
}
