<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class DateFilter extends SingleFieldFilter
{
    protected function query(Builder $query, array $values): Builder
    {
        return match ($this->mode) {
            FilterMode::LOWER => $query->whereDate($this->field, '<', $values[$this->field]),
            FilterMode::LOWER_OR_EQUAL => $query->whereDate($this->field, '<=', $values[$this->field]),
            FilterMode::GREATER => $query->whereDate($this->field, '>', $values[$this->field]),
            FilterMode::GREATER_OR_EQUAL => $query->whereDate($this->field, '>=', $values[$this->field]),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->whereDate($this->field, '>=', $values[$this->field][0])
                    ->whereDate($this->field, '<=', $values[$this->field][1])
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->whereDate($this->field, '>', $values[$this->field][0])
                    ->whereDate($this->field, '<', $values[$this->field][1])
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->orWhereDate($this->field, '<', $values[$this->field][0])
                    ->orWhereDate($this->field, '>', $values[$this->field][1])
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->orWhereDate($this->field, '<=', $values[$this->field][0])
                    ->orWhereDate($this->field, '>=', $values[$this->field][1])
            ),
            default => $query->whereDate($this->field, $values[$this->field]),
        };
    }

    protected function prepareValues(array|string $values): array
    {
        return Arr::map(parent::prepareValues($values), fn ($value) => is_array($value)
            ? array_values(Arr::sort(Arr::map($value, fn ($date) => $this->getValueForFilter($date))))
            : $this->getValueForFilter($value));
    }

    protected function getValueForFilter(string $value): mixed
    {
        return Carbon::parse($value);
    }

    public function rules(): array
    {
        return match ($this->mode->needsMultipleValues()) {
            true => [
                $this->field => 'required|array|size:2',
                $this->field . '.*' => 'date_format:Y-m-d',
            ],
            false => [
                $this->field => 'required|date_format:Y-m-d',
            ],
        };
    }
}
