<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class DateFilter extends SingleFieldFilter
{
    protected string $component = 'date';

    public function populate(string|array $values): self
    {
        parent::populate($values);

        $this->values = Arr::map($this->values, fn ($value) => is_array($value)
            ? array_values(Arr::sort($value))
            : $value);

        return $this;
    }

    public function apply(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::LOWER => $query->whereDate($this->field, '<', $this->values[$this->field]),
            FilterMode::LOWER_OR_EQUAL => $query->where($this->field, '<=', $this->values[$this->field] . ' 23:59:59'),
            FilterMode::GREATER => $query->whereDate($this->field, '>', $this->values[$this->field]),
            FilterMode::GREATER_OR_EQUAL => $query->where($this->field, '>=', $this->values[$this->field] . ' 00:00:00'),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->where($this->field, '>=', $this->values[$this->field][0] . ' 00:00:00')
                    ->where($this->field, '<=', $this->values[$this->field][1] . ' 23:59:59')
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->where($this->field, '>', $this->values[$this->field][0] . ' 23:59:59')
                    ->where($this->field, '<', $this->values[$this->field][1] . ' 00:00:00')
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->orWhere($this->field, '<', $this->values[$this->field][0] . ' 00:00:00')
                    ->orWhere($this->field, '>', $this->values[$this->field][1] . ' 23:59:59')
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->orWhere($this->field, '<=', $this->values[$this->field][0] . ' 23:59:59')
                    ->orWhere($this->field, '>=', $this->values[$this->field][1] . ' 00:00:00')
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
