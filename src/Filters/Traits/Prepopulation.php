<?php

namespace Lacodix\LaravelModelFilter\Filters\Traits;

use Illuminate\Support\Collection;

trait Prepopulation
{
    public function options(): array
    {
        return $this->model?->query()
            ->distinct($this->field)
            ->select($this->field)
            ->pluck($this->field)
            ->when(
                method_exists($this, 'mapOption'),
                static fn (Collection $options) => $options->map(static fn ($option) => $this->mapOption($option))
            )
            ->filter()
            ->toArray();
    }
}
