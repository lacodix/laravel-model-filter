<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * @template TModel of Model
 *
 * @extends Filter<TModel>
 */
abstract class SingleFieldFilter extends Filter
{
    protected string $field;
    protected ?string $table = null;
    protected bool $reindexesArrayInput = false;

    public function __construct(?string $field = null)
    {
        if ($field) {
            $this->field = $field;
        }
    }

    public function populate(string|array|null $values): static
    {
        if (is_null($values)) {
            $this->setValues([]);

            return $this;
        }

        if (
            $this->populatingFromScope
            || ! is_array($values)
            || ! Arr::isAssoc($values)
            || ! Arr::has($values, $this->queryName())
        ) {
            $values = [
                $this->queryName() => $values,
            ];
        }

        $values[$this->queryName()] = $this->normalizeInputValue(
            $values[$this->queryName()]
        );

        if ($this->reindexesArrayInput) {
            $values = Arr::map(
                $values,
                static fn ($value) => is_array($value) ? array_values($value) : $value
            );
        }

        $this->setValues($values);

        return $this;
    }

    protected function expectsListInput(): bool
    {
        return false;
    }

    protected function normalizeInputValue(mixed $value): mixed
    {
        return $value;
    }

    protected function hasFilterValue(): bool
    {
        return $this->values !== [];
    }

    protected function validateInputShape(Validator $validator): void
    {
        $attribute = $this->queryName();
        $value = $this->getValue();

        if (is_null($value)) {
            return;
        }

        if (! $this->expectsListInput()) {
            if (! $this->isScalarInput($value)) {
                $this->addInputShapeError($validator, $attribute);
            }

            return;
        }

        if (! is_array($value)) {
            $this->addInputShapeError($validator, $attribute);

            return;
        }

        foreach ($value as $key => $item) {
            if (! $this->isScalarInput($item)) {
                $this->addInputShapeError($validator, $attribute.'.'.$key);
            }
        }
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
        $query = $this->relationQuery ?? $this->model?->query();

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
