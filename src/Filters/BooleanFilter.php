<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
class BooleanFilter extends Filter
{
    protected string $component = 'boolean';

    public function __construct(?array $options = null)
    {
        $this->options = $options ?? $this->options ?? [];
    }

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        foreach ($this->options() as $key) {
            $query->when(
                ! is_null($this->values[$key] ?? null),
                fn ($query) => $query->where($key, $this->getValueForFilter($this->values[$key]))
            );
        }

        return $query;
    }

    protected function getValueForFilter(string $value): bool
    {
        return (bool) $value;
    }
}
