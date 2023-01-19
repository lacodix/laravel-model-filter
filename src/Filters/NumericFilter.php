<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class NumericFilter extends SingleFieldFilter
{
    protected function query(Builder $query, array $values): Builder
    {
        return match ($this->mode) {
            FilterMode::LOWER => $query->where($this->field, '<', $values[$this->field]),
            FilterMode::LOWER_OR_EQUAL => $query->where($this->field, '<=', $values[$this->field]),
            FilterMode::GREATER => $query->where($this->field, '>', $values[$this->field]),
            FilterMode::GREATER_OR_EQUAL => $query->where($this->field, '>=', $values[$this->field]),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->where($this->field, '>=', $values[$this->field][0])
                    ->where($this->field, '<=', $values[$this->field][1])
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->where($this->field, '>', $values[$this->field][0])
                    ->where($this->field, '<', $values[$this->field][1])
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->orWhere($this->field, '<', $values[$this->field][0])
                    ->orWhere($this->field, '>', $values[$this->field][1])
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->orWhere($this->field, '<=', $values[$this->field][0])
                    ->orWhere($this->field, '>=', $values[$this->field][1])
            ),
            default => $query->where($this->field, $values[$this->field]),
        };
    }

    protected function prepareValues(array|string $values): array
    {
        return Arr::map(parent::prepareValues($values), fn ($value) => is_array($value)
            ? array_values(Arr::sort($value))
            : $value);
    }

    public function rules(): array
    {
        return match ($this->mode->needsMultipleValues()) {
            true => [
                $this->field => 'required|array|size:2',
                $this->field . '.*' => 'numeric',
            ],
            false => [
                $this->field => 'required|numeric',
            ],
        };
    }
}
