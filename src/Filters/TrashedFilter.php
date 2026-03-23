<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends SelectFilter<TModel>
 */
class TrashedFilter extends SelectFilter
{
    public function title(): string
    {
        return trans('model-filter::filters.trashed');
    }

    /**
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function applyFilter(Builder $query): Builder
    {
        return match ($this->getValue()) {
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

    public function populate(string|array|null $values): static
    {
        $this->values = [$this->queryName() => $values];

        return $this;
    }
}
