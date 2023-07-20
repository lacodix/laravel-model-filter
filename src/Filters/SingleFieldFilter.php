<?php

namespace Lacodix\LaravelModelFilter\Filters;

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

    public function populate(string|array $values): static
    {
        if (! is_array($values) || ! Arr::isAssoc($values) || ! Arr::has($values, $this->field)) {
            $values = [
                $this->field => $values,
            ];
        }

        $this->values = $values;

        return $this;
    }

    public function field(string $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function getField(): ?string
    {
        return $this->field ?? null;
    }
}
