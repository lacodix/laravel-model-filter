<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

/**
 * @template TModel of Model
 */
class NumericFilter extends SingleFieldFilter
{
    protected string $component = 'numeric';

    protected int $min;
    protected int $max;

    public function populate(string|array|null $values): static
    {
        parent::populate($values);

        $this->values = Arr::map($this->values, static fn ($value) => is_array($value)
            ? array_values($value)
            : $value);

        return $this;
    }

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::LOWER => $query->where($this->getQualifiedField(), '<', $this->getValue()),
            FilterMode::LOWER_OR_EQUAL => $query->where($this->getQualifiedField(), '<=', $this->getValue()),
            FilterMode::GREATER => $query->where($this->getQualifiedField(), '>', $this->getValue()),
            FilterMode::GREATER_OR_EQUAL => $query->where($this->getQualifiedField(), '>=', $this->getValue()),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(is_numeric($this->getValue()[0] ?? null), fn ($q) => $q->where($this->getQualifiedField(), '>=', $this->getValue()[0]))
                    ->when(is_numeric($this->getValue()[1] ?? null), fn ($q) => $q->where($this->getQualifiedField(), '<=', $this->getValue()[1]))
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(is_numeric($this->getValue()[0] ?? null), fn ($q) => $q->where($this->getQualifiedField(), '>', $this->getValue()[0]))
                    ->when(is_numeric($this->getValue()[1] ?? null), fn ($q) => $q->where($this->getQualifiedField(), '<', $this->getValue()[1]))
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(is_numeric($this->getValue()[0] ?? null), fn ($q) => $q->orWhere($this->getQualifiedField(), '<', $this->getValue()[0]))
                    ->when(is_numeric($this->getValue()[1] ?? null), fn ($q) => $q->orWhere($this->getQualifiedField(), '>', $this->getValue()[1]))
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(is_numeric($this->getValue()[0] ?? null), fn ($q) => $q->orWhere($this->getQualifiedField(), '<=', $this->getValue()[0]))
                    ->when(is_numeric($this->getValue()[1] ?? null), fn ($q) => $q->orWhere($this->getQualifiedField(), '>=', $this->getValue()[1]))
            ),
            default => $query->where($this->getQualifiedField(), $this->getValue()),
        };
    }

    public function rules(): array
    {
        return match ($this->mode->needsMultipleValues()) {
            true => [
                $this->queryName() => 'required|array|size:2',
                $this->queryName() . '.*' => 'numeric' . $this->getMinRule() . $this->getMaxRule(),
            ],
            false => [
                $this->queryName() => 'required|numeric' . $this->getMinRule() . $this->getMaxRule(),
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
