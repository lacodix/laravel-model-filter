<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class NumericFilter extends SingleFieldFilter
{
    protected string $component = 'numeric';

    protected int $min;
    protected int $max;

    public function populate(string|array $values): static
    {
        parent::populate($values);

        $this->values = Arr::map($this->values, static fn ($value) => is_array($value)
            ? array_values($value)
            : $value);

        return $this;
    }

    public function apply(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::LOWER => $query->where($this->getQualifiedField(), '<', $this->values[$this->field]),
            FilterMode::LOWER_OR_EQUAL => $query->where($this->getQualifiedField(), '<=', $this->values[$this->field]),
            FilterMode::GREATER => $query->where($this->getQualifiedField(), '>', $this->values[$this->field]),
            FilterMode::GREATER_OR_EQUAL => $query->where($this->getQualifiedField(), '>=', $this->values[$this->field]),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(is_numeric($this->values[$this->field][0]), fn ($q) => $q->where($this->getQualifiedField(), '>=', $this->values[$this->field][0]))
                    ->when(is_numeric($this->values[$this->field][1]), fn ($q) => $q->where($this->getQualifiedField(), '<=', $this->values[$this->field][1]))
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(is_numeric($this->values[$this->field][0]), fn ($q) => $q->where($this->getQualifiedField(), '>', $this->values[$this->field][0]))
                    ->when(is_numeric($this->values[$this->field][1]), fn ($q) => $q->where($this->getQualifiedField(), '<', $this->values[$this->field][1]))
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(is_numeric($this->values[$this->field][0]), fn ($q) => $q->orWhere($this->getQualifiedField(), '<', $this->values[$this->field][0]))
                    ->when(is_numeric($this->values[$this->field][1]), fn ($q) => $q->orWhere($this->getQualifiedField(), '>', $this->values[$this->field][1]))
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(is_numeric($this->values[$this->field][0]), fn ($q) => $q->orWhere($this->getQualifiedField(), '<=', $this->values[$this->field][0]))
                    ->when(is_numeric($this->values[$this->field][1]), fn ($q) => $q->orWhere($this->getQualifiedField(), '>=', $this->values[$this->field][1]))
            ),
            default => $query->where($this->getQualifiedField(), $this->values[$this->field]),
        };
    }

    public function rules(): array
    {
        return match ($this->mode->needsMultipleValues()) {
            true => [
                $this->field => 'required|array|size:2',
                $this->field . '.*' => 'numeric' . $this->getMinRule() . $this->getMaxRule(),
            ],
            false => [
                $this->field => 'required|numeric' . $this->getMinRule() . $this->getMaxRule(),
            ],
        };
    }

    public function getMin(): ?int
    {
        return $this->min ?? null;
    }

    public function getMax(): ?int
    {
        return $this->max ?? null;
    }

    protected function getMinRule(): string
    {
        return isset($this->min) ? '|min:' . $this->min : '';
    }

    protected function getMaxRule(): string
    {
        return isset($this->max) ? '|max:' . $this->max : '';
    }
}
