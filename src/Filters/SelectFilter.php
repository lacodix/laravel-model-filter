<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class SelectFilter extends SingleFieldFilter
{
    protected string $component = 'select';

    public function apply(Builder $query): Builder
    {
        if (is_int(Arr::first($this->options()))) {
            $this->values[$this->field] = (int)$this->values[$this->field];
        }

        return $query
            ->when(
                in_array($this->values[$this->field], $this->options()),
                fn ($query) => $query->where($this->field, $this->values[$this->field])
            );
    }

    public function rules(): array
    {
        return [
            'type' => 'in:' . implode(',', $this->options()),
        ];
    }
}
