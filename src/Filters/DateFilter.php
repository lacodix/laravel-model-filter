<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class DateFilter extends SingleFieldFilter
{
    public function values(string|array $values): self
    {
        parent::values($values);

        $this->values = Arr::map($this->values, fn ($value) => is_array($value)
            ? array_values(Arr::sort($value))
            : $value);

        return $this;
    }

    protected function query(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::LOWER => $query->whereDate($this->field, '<', $this->values[$this->field]),
            FilterMode::LOWER_OR_EQUAL => $query->whereDate($this->field, '<=', $this->values[$this->field]),
            FilterMode::GREATER => $query->whereDate($this->field, '>', $this->values[$this->field]),
            FilterMode::GREATER_OR_EQUAL => $query->whereDate($this->field, '>=', $this->values[$this->field]),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->whereDate($this->field, '>=', $this->values[$this->field][0])
                    ->whereDate($this->field, '<=', $this->values[$this->field][1])
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->whereDate($this->field, '>', $this->values[$this->field][0])
                    ->whereDate($this->field, '<', $this->values[$this->field][1])
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->orWhereDate($this->field, '<', $this->values[$this->field][0])
                    ->orWhereDate($this->field, '>', $this->values[$this->field][1])
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->orWhereDate($this->field, '<=', $this->values[$this->field][0])
                    ->orWhereDate($this->field, '>=', $this->values[$this->field][1])
            ),
            default => $query->whereDate($this->field, $this->values[$this->field]),
        };
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
                $this->field . '.*' => 'date_format:' . config('model-filter.date_format'),
            ],
            false => [
                $this->field => 'required|date_format:' . config('model-filter.date_format'),
            ],
        };
    }
}
