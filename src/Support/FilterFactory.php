<?php

namespace Lacodix\LaravelModelFilter\Support;

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Filters\Filter;

/**
 * @template TModel of Model
 * @template TFilter of Filter<TModel>
 */
class FilterFactory
{
    /**
     * @param class-string<TFilter> $filterClass
     */
    public function __construct(private readonly string $filterClass)
    {
    }

    /**
     * @return TFilter
     */
    public function make(string $field): Filter
    {
        $class = $this->filterClass;

        /** @var TFilter $filter */
        return $class::make($field);
    }
}
