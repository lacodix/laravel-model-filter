<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class SingleFieldFilter extends Filter
{
    protected string $field;
    protected ?string $table = null;

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

    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getQualifiedField(): string
    {
        if (Str::contains($this->field, '.') || is_null($this->table)) {
            return $this->field;
        }

        return $this->table . '.' . $this->field;
    }
}
