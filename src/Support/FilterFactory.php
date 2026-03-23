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
    /** @var class-string<TFilter> */
    private string $filterClass;

    /**
     * @param class-string<TFilter> $filterClass
     */
    public function __construct(string $filterClass)
    {
        $this->filterClass = $filterClass;
    }

    /**
     * @return TFilter
     */
    public function make(string $field): Filter
    {
        $class = $this->filterClass;

        /** @var TFilter $filter */
        $filter = $class::make($field);

        return $filter;
    }
}
