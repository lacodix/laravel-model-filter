<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @template TModel of Model
 *
 * @extends Filter<TModel>
 */
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

    public function populate(string|array|null $values): static
    {
        if (is_null($values)) {
            $this->values = [];

            return $this;
        }

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
        $query = $this->model?->query();
        if (! $query) {
            return $this->field;
        }

        return $query->qualifyColumn($this->getField());
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
        if (str_contains(static::class, '@anonymous')) {
            $this->queryName = Str::snake($this->field);
        }

        return parent::queryName();
    }
}
