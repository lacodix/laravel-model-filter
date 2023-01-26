<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

abstract class SingleFieldFilter extends Filter
{
    protected string $field;

    public function __construct(?string $field = null)
    {
        if ($field) {
            $this->field = $field;
        }
    }

    public function values(string|array $values): self
    {
        if (! is_array($values) || ! Arr::isAssoc($values) || ! Arr::has($values, $this->field)) {
            $values = [
                $this->field => $values,
            ];
        }

        $this->values = $values;

        return $this;
    }

    public function apply(Builder $query): Builder
    {
        return $this->query($query);
    }

    abstract protected function query(Builder $query): Builder;
}
