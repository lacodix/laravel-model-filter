<?php

namespace Tests\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;

class CounterFilter extends NumericFilter
{
    protected string $field = 'counter';

    protected int $min = 0;
    protected int $max = 20000;

    public FilterMode $mode = FilterMode::LOWER_OR_EQUAL;
}
