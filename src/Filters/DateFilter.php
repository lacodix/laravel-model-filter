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
        return match ($this->mode) {
            FilterMode::LOWER => $query->whereDate($this->getQualifiedField(), '<', $this->values[$this->field]),
            FilterMode::LOWER_OR_EQUAL => $query->where($this->getQualifiedField(), '<=', $this->values[$this->field] . ' 23:59:59'),
            FilterMode::GREATER => $query->whereDate($this->getQualifiedField(), '>', $this->values[$this->field]),
            FilterMode::GREATER_OR_EQUAL => $query->where($this->getQualifiedField(), '>=', $this->values[$this->field] . ' 00:00:00'),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(! empty($this->values[$this->field][0]), fn ($q) => $q->where($this->getQualifiedField(), '>=', $this->values[$this->field][0] . ' 00:00:00'))
                    ->when(! empty($this->values[$this->field][1]), fn ($q) => $q->where($this->getQualifiedField(), '<=', $this->values[$this->field][1] . ' 23:59:59'))
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(! empty($this->values[$this->field][0]), fn ($q) => $q->where($this->getQualifiedField(), '>', $this->values[$this->field][0] . ' 23:59:59'))
                    ->when(! empty($this->values[$this->field][1]), fn ($q) => $q->where($this->getQualifiedField(), '<', $this->values[$this->field][1] . ' 00:00:00'))
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(! empty($this->values[$this->field][0]), fn ($q) => $q->orWhere($this->getQualifiedField(), '<', $this->values[$this->field][0] . ' 00:00:00'))
                    ->when(! empty($this->values[$this->field][1]), fn ($q) => $q->orWhere($this->getQualifiedField(), '>', $this->values[$this->field][1] . ' 23:59:59'))
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(! empty($this->values[$this->field][0]), fn ($q) => $q->orWhere($this->getQualifiedField(), '<=', $this->values[$this->field][0] . ' 23:59:59'))
                    ->when(! empty($this->values[$this->field][1]), fn ($q) => $q->orWhere($this->getQualifiedField(), '>=', $this->values[$this->field][1] . ' 00:00:00'))
            ),
            default => $query->whereDate($this->getQualifiedField(), $this->values[$this->field]),
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
