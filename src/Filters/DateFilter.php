<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class DateFilter extends SingleFieldFilter
{
    protected string $component = 'date';

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
        $qualifiedField = $this->getQualifiedField();

        return match ($this->mode) {
            FilterMode::LOWER => $query->whereDate($qualifiedField, '<', $this->values[$this->field]),
            FilterMode::LOWER_OR_EQUAL => $query->whereDate($qualifiedField, '<=', $this->values[$this->field]),
            FilterMode::GREATER => $query->whereDate($qualifiedField, '>', $this->values[$this->field]),
            FilterMode::GREATER_OR_EQUAL => $query->whereDate($qualifiedField, '>=', $this->values[$this->field]),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(
                        ! empty($this->values[$this->field][0]),
                        fn ($q) => $q->whereDate($qualifiedField, '>=', $this->values[$this->field][0])
                    )
                    ->when(
                        ! empty($this->values[$this->field][1]),
                        fn ($q) => $q->whereDate($qualifiedField, '<=', $this->values[$this->field][1])
                    )
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(
                        ! empty($this->values[$this->field][0]),
                        fn ($q) => $q->whereDate($qualifiedField, '>', $this->values[$this->field][0])
                    )
                    ->when(
                        ! empty($this->values[$this->field][1]),
                        fn ($q) => $q->whereDate($qualifiedField, '<', $this->values[$this->field][1])
                    )
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(
                        ! empty($this->values[$this->field][0]),
                        fn ($q) => $q->orWhereDate($qualifiedField, '<', $this->values[$this->field][0])
                    )
                    ->when(
                        ! empty($this->values[$this->field][1]),
                        fn ($q) => $q->orWhereDate($qualifiedField, '>', $this->values[$this->field][1])
                    )
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(
                        ! empty($this->values[$this->field][0]),
                        fn ($q) => $q->orWhereDate($qualifiedField, '<=', $this->values[$this->field][0])
                    )
                    ->when(
                        ! empty($this->values[$this->field][1]),
                        fn ($q) => $q->orWhereDate($qualifiedField, '>=', $this->values[$this->field][1])
                    )
            ),
            default => $query->whereDate($qualifiedField, $this->values[$this->field]),
        };
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

    protected function getValueForFilter(string $value): mixed
    {
        return Carbon::parse($value);
    }
}
