<?php

namespace Lacodix\LaravelModelFilter\Traits;

trait Makeable
{
    /**
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments); // @phpstan-ignore-line
    }
}
