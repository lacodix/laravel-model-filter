<?php

namespace Lacodix\LaravelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Lacodix\LaravelFilter\Enums\FilterMode;

class DateFilter extends SingleFieldFilter
{
    public function apply(Builder $query, string|array $values): Builder
    {
        return $this->query($query, is_array($values) ? current($values) : $values);
    }

    protected function query(Builder $query, string $value): Builder
    {
        $value = $this->getValueForFilter($value);

        return match ($this->mode) {
            FilterMode::LOWER => $query->where($this->field, '<', $value),
            FilterMode::LOWER_OR_EQUAL => $query->where($this->field, '<=', $value),
            FilterMode::GREATER => $query->where($this->field, '>', $value),
            FilterMode::GREATER_OR_EQUAL => $query->where($this->field, '>=', $value),
            default => $query->where($this->field, $value),
        };
    }

    protected function getValueForFilter(string $value): Carbon
    {
        return Carbon::parse($value);
    }
}
