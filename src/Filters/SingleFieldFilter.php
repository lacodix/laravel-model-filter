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
        if (! is_array($values) || ! Arr::isAssoc($values) || ! Arr::has($values, $this->queryName())) {
            $values = [
                $this->queryName() => $values,
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

    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getQualifiedField(): string
    {
        $field = $this->field;

        if (Str::contains($field, '.') && ! is_null($this->table) && Str::startsWith($field, $this->table . '.')) {
            $field = Str::after($field, $this->table . '.');
        }

        if (Str::contains($field, '.')) {
            $field = str_replace('.', '->', $field);
        }

        $qualifiedField = is_null($this->table)
            ? $field
            : $this->table . '.' . $field;

        return $qualifiedField;
    }

    public function getField(): string
    {
        if (Str::contains($this->field, '.')) {
            return str_replace('.', '->', $this->field);
        }

        return $this->field;
    }

    public function queryName(): string
    {
        if (isset($this->queryName)) {
            return $this->queryName;
        }

        // special case - take field, when we are anonymouse and query_name not set
        if (str_contains($this::class, '@anonymous')) {
            $this->queryName = Str::snake($this->field);
        }

        return parent::queryName();
    }
}
