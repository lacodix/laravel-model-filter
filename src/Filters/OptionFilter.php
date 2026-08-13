<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

/**
 * @template TModel of Model
 *
 * @extends Filter<TModel>
 */
class OptionFilter extends Filter
{
    protected string $component = 'option';

    public function __construct(?array $options = null)
    {
        $this->options = $options ?? $this->options ?? [];
    }

    /**
     * @param  Builder<TModel> $query
     *
     * @return Builder<TModel>
     */
    public function applyFilter(Builder $query): Builder
    {
        foreach ($this->options() as $key) {
            $query->when(
                ! is_null($this->getValue($key)),
                fn ($query) => $query->where($key, $this->getValueForFilter($this->getValue($key)))
            );
        }

        return $query;
    }

    protected function getValueForFilter(string $value): bool
    {
        return (bool) $value;
    }

    protected function validateInputShape(Validator $validator): void
    {
        if ($this->values === []) {
            return;
        }

        if (array_is_list($this->values)) {
            $this->addInputShapeError($validator, $this->queryName());

            return;
        }

        $options = $this->options();
        $optionNames = array_is_list($options)
            ? $options
            : [...array_keys($options), ...array_values($options)];

        foreach (array_unique($optionNames) as $optionName) {
            if (
                array_key_exists($optionName, $this->values)
                && ! $this->isScalarInput($this->values[$optionName])
            ) {
                $this->addInputShapeError($validator, $this->queryName().'.'.$optionName);
            }
        }
    }
}
