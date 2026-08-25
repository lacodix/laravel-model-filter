<?php

declare(strict_types=1);

namespace Lacodix\LaravelModelFilter\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;
use Lacodix\LaravelModelFilter\Exceptions\UnknownFilterGroupException;
use Lacodix\LaravelModelFilter\Filters\Filter;

final class FilterPreparation
{
    public const DEFAULT_GROUP = '__default';

    /**
     * Resolve and populate the filters that are ready to reach Filter::apply().
     * No query is changed by this method.
     *
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  array<array-key, mixed>  $values
     * @param  (callable(Filter<TModel>): void)|null  $configure
     *
     * @return PreparedFilters<TModel>
     *
     * @throws InvalidArgumentException
     * @throws UnknownFilterGroupException
     * @throws ValidationException
     */
    public function prepare(
        Model $model,
        array $values,
        string $group,
        bool $strictGroup = false,
        ?callable $configure = null,
    ): PreparedFilters {
        $filters = $this->freshFilterInstances($model, $group, $strictGroup, $configure);
        $usableValues = collect($values)
            ->only($filters->map(static fn (Filter $filter): string => $filter->queryName())->all())
            ->filter(static fn (mixed $value): bool => isset($value) && $value !== '');

        /** @var list<Filter<TModel>> $prepared */
        $prepared = [];

        foreach ($filters as $filter) {
            $queryName = $filter->queryName();

            if (! $usableValues->has($queryName) || ! $filter->applicable()) {
                continue;
            }

            $value = $usableValues->get($queryName);
            $filter->populateFromScope(
                is_array($value) || is_null($value) ? $value : (string) $value
            );

            if ($filter->validationMode === ValidationMode::THROW) {
                $filter->validate();
            }

            if ($filter->fails() || ! $filter->readyToApply()) {
                continue;
            }

            $prepared[] = $filter;
        }

        return new PreparedFilters(...$prepared);
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  (callable(Filter<TModel>): void)|null  $configure
     *
     * @return Collection<int, Filter<TModel>>
     */
    private function freshFilterInstances(
        Model $model,
        string $group,
        bool $strictGroup,
        ?callable $configure,
    ): Collection {
        /** @var list<Filter<TModel>> $instances */
        $instances = [];

        foreach ($this->groupedFilters($model, $group, $strictGroup) as $filterOrName) {
            $instances[] = $this->freshFilterInstance($filterOrName);
        }

        foreach ($instances as $filter) {
            if ($configure !== null) {
                $configure($filter);
            }
        }

        /** @var list<Filter<TModel>> $visibleInstances */
        $visibleInstances = [];

        foreach ($instances as $filter) {
            if ($filter->visible()) {
                $visibleInstances[] = $filter;
            }
        }

        /** @var list<Filter<TModel>> $mappedInstances */
        $mappedInstances = [];

        foreach ($visibleInstances as $filter) {
            $filter->setModel($model);
            $mappedFilter = $this->mapFilter($filter, $model);

            if ($mappedFilter !== $filter) {
                $mappedFilter = $mappedFilter->newPreparationInstance();

                if ($configure !== null) {
                    $configure($mappedFilter);
                }

                $mappedFilter->setModel($model);
            }

            $mappedInstances[] = $mappedFilter;
        }

        return collect($mappedInstances);
    }

    /**
     * @template TModel of Model
     *
     * @param  Filter<TModel>|class-string<Filter<TModel>>  $filterOrName
     *
     * @return Filter<TModel>
     */
    private function freshFilterInstance(Filter|string $filterOrName): Filter
    {
        if ($filterOrName instanceof Filter) {
            return $filterOrName->newPreparationInstance();
        }

        /** @var Filter<TModel> $filter */
        $filter = new $filterOrName();

        return $filter;
    }

    /**
     * @template TModel of Model
     *
     * @param  Filter<TModel>  $filter
     * @param  TModel  $model
     *
     * @return Filter<TModel>
     */
    private function mapFilter(Filter $filter, Model $model): Filter
    {
        if (! $filter->hasMacro('mapFilter')) {
            return $filter;
        }

        $mappedFilter = $filter->__call('mapFilter', [$model]);

        if (! $mappedFilter instanceof Filter) {
            throw new InvalidArgumentException('The mapFilter macro must return a filter instance.');
        }

        /** @var Filter<TModel> $mappedFilter */
        return $mappedFilter;
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     *
     * @return Collection<array-key, Filter<TModel>|class-string<Filter<TModel>>>
     */
    private function groupedFilters(Model $model, string $group, bool $strictGroup): Collection
    {
        if (! method_exists($model, 'filters')) {
            throw new InvalidArgumentException(sprintf(
                'Model [%s] does not expose filters().',
                $model::class
            ));
        }

        $filters = $model->filters();

        if (! is_array($filters)) {
            throw new InvalidArgumentException(sprintf(
                'Model [%s] must return an array from filters().',
                $model::class
            ));
        }

        if (! Arr::isAssoc($filters)) {
            if ($strictGroup && $group !== self::DEFAULT_GROUP) {
                $this->throwUnknownGroup($model, $group);
            }

            /** @var Collection<array-key, Filter<TModel>|class-string<Filter<TModel>>> $filterCollection */
            $filterCollection = collect($filters);

            return $filterCollection;
        }

        if ($strictGroup && ! array_key_exists($group, $filters)) {
            $this->throwUnknownGroup($model, $group);
        }

        /** @var Collection<array-key, Filter<TModel>|class-string<Filter<TModel>>> $filterCollection */
        $filterCollection = collect($filters[$group] ?? $filters[self::DEFAULT_GROUP] ?? []);

        return $filterCollection;
    }

    private function throwUnknownGroup(Model $model, string $group): never
    {
        throw new UnknownFilterGroupException(sprintf(
            'Filter group [%s] is not defined for model [%s].',
            $group,
            $model::class
        ));
    }
}
