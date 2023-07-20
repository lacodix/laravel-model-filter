<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;

class TrashedFilter extends SelectFilter
{
    public function title(): string
    {
        return trans('model-filter::filters.trashed');
    }

    public function apply(Builder $query): Builder
    {
        return match (current($this->values)) {
            'with_trashed' => $query->withTrashed(), // @phpstan-ignore-line
            'only_trashed' => $query->onlyTrashed(), // @phpstan-ignore-line
            default => $query,
        };
    }

    public function options(): array
    {
        return [
            trans('model-filter::filters.with_trashed') => 'with_trashed',
            trans('model-filter::filters.only_trashed') => 'only_trashed',
        ];
    }

    public function rules(): array
    {
        return [
            'type' => 'in:with_trashed,only_trashed',
        ];
    }

    public function populate(string|array $values): static
    {
        $this->values = [$values];

        return $this;
    }
}
