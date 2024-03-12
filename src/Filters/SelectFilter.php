<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

class SelectFilter extends SingleFieldFilter
{
    protected string $component = 'select';

    public function apply(Builder $query): Builder
    {
        return match ($this->mode) {
            FilterMode::CONTAINS => $query->whereIn(
                $this->getQualifiedField(),
                array_intersect($this->values[$this->field], $this->options())
            ),
            default => $query
                ->when(
                    in_array($this->values[$this->field], $this->options()),
                    fn ($query) => $query->where($this->getQualifiedField(), $this->values[$this->field])
                ),
        };
    }

    public function rules(): array
    {
        return $this->mode === FilterMode::CONTAINS ? $this->multiRules() : $this->singleRules();
    }

    protected function singleRules(): array
    {
        return [
            $this->field => 'in:' . implode(',', $this->options()),
        ];
    }

    protected function multiRules(): array
    {
        return [
            $this->field => 'array',
            $this->field . '.*' => 'in:' . implode(',', $this->options()),
        ];
    }
}
