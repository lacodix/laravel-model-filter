<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

/**
 * @template TModel of Model
 *
 * @extends SingleFieldFilter<TModel>
 */
class DateFilter extends SingleFieldFilter
{
    protected string $component = 'date';

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
    public function applyFilter(Builder $query): Builder
    {
        $qualifiedField = $this->getQualifiedField();

        return match ($this->mode) {
            FilterMode::LOWER => $query->whereDate($qualifiedField, '<', $this->getValue()),
            FilterMode::LOWER_OR_EQUAL => $query->whereDate($qualifiedField, '<=', $this->getValue()),
            FilterMode::GREATER => $query->whereDate($qualifiedField, '>', $this->getValue()),
            FilterMode::GREATER_OR_EQUAL => $query->whereDate($qualifiedField, '>=', $this->getValue()),
            FilterMode::BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(
                        ! empty($this->getValue()[0] ?? null),
                        fn ($q) => $q->whereDate($qualifiedField, '>=', $this->getValue()[0])
                    )
                    ->when(
                        ! empty($this->getValue()[1] ?? null),
                        fn ($q) => $q->whereDate($qualifiedField, '<=', $this->getValue()[1])
                    )
            ),
            FilterMode::BETWEEN_EXCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(
                        ! empty($this->getValue()[0] ?? null),
                        fn ($q) => $q->whereDate($qualifiedField, '>', $this->getValue()[0])
                    )
                    ->when(
                        ! empty($this->getValue()[1] ?? null),
                        fn ($q) => $q->whereDate($qualifiedField, '<', $this->getValue()[1])
                    )
            ),
            FilterMode::NOT_BETWEEN => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(
                        ! empty($this->getValue()[0] ?? null),
                        fn ($q) => $q->orWhereDate($qualifiedField, '<', $this->getValue()[0])
                    )
                    ->when(
                        ! empty($this->getValue()[1] ?? null),
                        fn ($q) => $q->orWhereDate($qualifiedField, '>', $this->getValue()[1])
                    )
            ),
            FilterMode::NOT_BETWEEN_INCLUSIVE => $query->where(
                fn (Builder $betweenQuery) => $betweenQuery
                    ->when(
                        ! empty($this->getValue()[0] ?? null),
                        fn ($q) => $q->orWhereDate($qualifiedField, '<=', $this->getValue()[0])
                    )
                    ->when(
                        ! empty($this->getValue()[1] ?? null),
                        fn ($q) => $q->orWhereDate($qualifiedField, '>=', $this->getValue()[1])
                    )
            ),
            default => $query->whereDate($qualifiedField, $this->getValue()),
        };
    }

    public function rules(): array
    {
        return match ($this->mode->needsMultipleValues()) {
            true => [
                $this->queryName() => 'required|array|size:2',
                $this->queryName() . '.*' => 'date_format:' . config('model-filter.date_format'),
            ],
            false => [
                $this->queryName() => 'required|date_format:' . config('model-filter.date_format'),
            ],
        };
    }

    protected function getValueForFilter(string $value): mixed
    {
        return Carbon::parse($value);
    }
}
