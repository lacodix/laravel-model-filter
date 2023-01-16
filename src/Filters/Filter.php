<?php

namespace Lacodix\LaravelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Lacodix\LaravelFilter\Enums\FilterMode;

abstract class Filter
{
    protected FilterMode $mode = FilterMode::EQUAL;

    protected array $options;

    public function queryName(string|int $key): string
    {
        return is_int($key) ? Str::snake(class_basename(static::class)) : $key;
    }

    abstract public function apply(Builder $query, string|array $values): Builder;

    public function options(): array
    {
        return $this->options ?? [];
    }
}
