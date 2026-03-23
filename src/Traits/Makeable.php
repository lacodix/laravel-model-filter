<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Support\FilterFactory;

trait Makeable
{
    /**
     * @template TConcreteModel of Model
     * @param class-string<TConcreteModel> $modelClass
     * @return FilterFactory<TConcreteModel, static<TConcreteModel>>
     */
    public static function forModel(string $modelClass): FilterFactory
    {
        /** @var class-string<static<TConcreteModel>> $filterClass */
        $filterClass = static::class;

        return new FilterFactory($filterClass, $modelClass);
    }

    /**
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments); // @phpstan-ignore-line
    }
}
