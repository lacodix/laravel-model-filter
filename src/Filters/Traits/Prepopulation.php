<?php

namespace Lacodix\LaravelModelFilter\Filters\Traits;

use Illuminate\Support\Collection;

trait Prepopulation
{
    public function options(): array
    {
        $field = method_exists($this, 'getQualifiedField') ? $this->getQualifiedField() : $this->field;

        $results = $this->model?->query()
            ->distinct($field)
            ->select($field)
            ->get();

        return $this->options ??= $results
            ->map(fn ($row) => data_get($row, method_exists($this, 'getField') ? $this->getField() : $field))
            ->when(
                method_exists($this, 'mapOption'),
                static fn (Collection $options) => $options->map(static fn ($option) => $this->mapOption($option))
            )
            ->filter()
            ->toArray();
    }
}
