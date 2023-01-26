<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends SingleFieldFilter
{
    protected function query(Builder $query): Builder
    {
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
