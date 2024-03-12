<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;

class BelongsToFilter extends SelectFilter
{
    protected string $component = 'select';

    protected string $relationModel;
    protected string $idColumn = 'id';
    protected string $titleColumn;

    public function options(): array
    {
        if (! isset($this->relationModel) || ! isset($this->titleColumn)) {
            throw new InvalidArgumentException('The relation model and title column must be set.');
        }

        if (! class_exists($this->relationModel)) {
            throw new InvalidArgumentException("The relation model {$this->relationModel} does not exist.");
        }

        if (! is_subclass_of($this->relationModel, Model::class)) {
            throw new InvalidArgumentException("The relation model {$this->relationModel} is not an eloquent model.");
        }

        return $this->relationQuery()
            ->pluck($this->idColumn, $this->titleColumn)
            ->sortKeys()
            ->when(
                method_exists($this, 'mapTitle'),
                fn ($collection) => $collection->mapWithKeys(fn ($value, $key) => [$this->mapTitle($key) => $value]), // @phpstan-ignore-line
            )
            ->toArray();
    }

    public function relationQuery(): Builder
    {
        return $this->relationModel::query();
    }
}
