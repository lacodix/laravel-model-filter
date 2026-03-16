<?php

namespace Lacodix\LaravelModelFilter\Filters\Traits;

use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Filters\SingleFieldFilter;

trait Prepopulation
{
    public function options(): array
    {
        if (! $this instanceof SingleFieldFilter) {
            return [];
        }

        $query = $this->model?->query();
        if (! $query) {
            return [];
        }

        $field = $query->qualifyColumn($this->getField());

        return $this->options ??= $this->model?->query()
            ->distinct($field)
            ->select($field . ' as ' . '_lmf_prepop_field')
            ->pluck('_lmf_prepop_field')
            ->filter()
            ->mapWithKeys(fn ($option) => [
                method_exists($this, 'mapOption') ? $this->mapOption($option) : $option => $option,
            ])
            ->toArray();
    }
}
