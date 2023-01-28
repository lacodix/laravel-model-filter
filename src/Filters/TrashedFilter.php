<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;

class TrashedFilter extends SelectFilter
{
    public function apply(Builder $query): Builder
    {
        return match (current($this->values)) {
            'with_trashed' => $query->withTrashed(),
            'only_trashed' => $query->onlyTrashed(),
            default => $query,
        };
    }

    public function options(): array
    {
        return [
            trans('lacodix-filter::filters.with_trashed') => 'with_trashed',
            trans('lacodix-filter::filters.only_trashed') => 'only_trashed',
        ];
    }

    public function rules(): array
    {
        return [
            'type' => 'in:with_trashed,only_trashed',
        ];
    }
}
