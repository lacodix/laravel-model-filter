<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

/**
 * @template TModel of Model
 *
 * @extends SingleFieldFilter<TModel>
 */
class SelectFilter extends SingleFieldFilter
{
    protected string $component = 'select';

    protected bool $nullable = false;

    protected string $nullValue = '__null__';

    protected ?string $nullLabel = null;

    /**
     * Add an explicit option to filter for records where the field is NULL.
     */
    public function nullable(bool $nullable = true, ?string $label = null): static
    {
        $this->nullable = $nullable;
        $this->nullLabel = $label;

        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * The options as shown/selectable in the filter, including the optional
     * "is null" option. Use this instead of options() for rendering and validation.
     *
     * @return array<int|string, mixed>
     */
    public function optionsWithNull(): array
    {
        if (! $this->nullable) {
            return $this->options();
        }

        return [$this->nullOptionLabel() => $this->nullValue] + $this->options();
    }

    /**
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function applyFilter(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::CONTAINS => $this->applyContains($query, negate: false),
            FilterMode::NOT_CONTAINS => $this->applyContains($query, negate: true),
            default => $this->applyEqual($query),
        };
    }

    public function rules(): array
    {
        return $this->mode === FilterMode::CONTAINS || $this->mode === FilterMode::NOT_CONTAINS
            ? $this->multiRules()
            : $this->singleRules();
    }

    /**
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyEqual(Builder $query): Builder
    {
        if ($this->nullable && $this->getValue() === $this->nullValue) {
            return $query->whereNull($this->getQualifiedField());
        }

        return $query->when(
            in_array($this->getValue(), $this->options()),
            fn ($query) => $query->where($this->getQualifiedField(), $this->getValue())
        );
    }

    /**
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyContains(Builder $query, bool $negate): Builder
    {
        $values = Arr::wrap($this->getValue());
        $realValues = array_intersect($values, $this->options());
        $includesNull = $this->nullable && in_array($this->nullValue, $values, true);

        $field = $this->getQualifiedField();

        if (! $includesNull) {
            return $negate
                ? $query->whereNotIn($field, $realValues)
                : $query->whereIn($field, $realValues);
        }

        return $query->where(function (Builder $query) use ($field, $realValues, $negate): void {
            if ($negate) {
                $query->whereNotIn($field, $realValues)->whereNotNull($field);
            } else {
                $query->whereIn($field, $realValues)->orWhereNull($field);
            }
        });
    }

    protected function singleRules(): array
    {
        return [
            $this->queryName() => 'in:'.implode(',', $this->optionsWithNull()),
        ];
    }

    protected function multiRules(): array
    {
        return [
            $this->queryName() => 'array',
            $this->queryName().'.*' => 'in:'.implode(',', $this->optionsWithNull()),
        ];
    }

    protected function nullOptionLabel(): string
    {
        return $this->nullLabel ?? trans('model-filter::filters.none');
    }
}
