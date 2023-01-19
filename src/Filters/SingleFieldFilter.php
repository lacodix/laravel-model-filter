<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

abstract class SingleFieldFilter extends Filter
{
    protected string $field;

    public function __construct(?string $field = null, ?FilterMode $mode = null)
    {
        if ($field) {
            $this->field = $field;
        }

        if ($mode !== null) {
            $this->mode = $mode;
        }
    }

    public function apply(Builder $query, string|array $values): Builder
    {
        return $this->query($query, $this->prepareValues($values));
    }

    abstract protected function query(Builder $query, array $values): Builder;

    protected function prepareValues(array|string $values): array
    {
        if (! is_array($values) || ! Arr::isAssoc($values) || ! Arr::has($values, $this->field)) {
            $values = [
                $this->field => $values,
            ];
        }

        $this->validate($values);

        return $values;
    }
}
