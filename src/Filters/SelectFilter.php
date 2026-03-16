<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

/**
 * @template TModel of Model
 */
class SelectFilter extends SingleFieldFilter
{
    protected string $component = 'select';

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::CONTAINS => $query->whereIn(
                $this->getQualifiedField(),
                array_intersect(Arr::wrap($this->values[$this->queryName()]), $this->options())
            ),
            FilterMode::NOT_CONTAINS => $query->whereNotIn(
                $this->getQualifiedField(),
                array_intersect(Arr::wrap($this->values[$this->queryName()]), $this->options())
            ),
            default => $query
                ->when(
                    in_array($this->values[$this->queryName()], $this->options()),
                    fn ($query) => $query->where($this->getQualifiedField(), $this->values[$this->queryName()])
                ),
        };
    }

    public function rules(): array
    {
        return $this->mode === FilterMode::CONTAINS || $this->mode === FilterMode::NOT_CONTAINS
            ? $this->multiRules()
            : $this->singleRules();
    }

    protected function singleRules(): array
    {
        return [
            $this->queryName() => 'in:' . implode(',', $this->options()),
        ];
    }

    protected function multiRules(): array
    {
        return [
            $this->queryName() => 'array',
            $this->queryName() . '.*' => 'in:' . implode(',', $this->options()),
        ];
    }
}
