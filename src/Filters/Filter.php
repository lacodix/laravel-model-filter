<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Validator;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Filters\Traits\HasPresets;
use Lacodix\LaravelModelFilter\Traits\Makeable;

/**
 * @template TModel of Model
 */
abstract class Filter
{
    use Macroable;
    use Conditionable;
    use HasPresets;
    use Makeable;

    public array $messages = [];
    public array $validationAttributes = [];

    public FilterMode $mode = FilterMode::EQUAL;
    public ValidationMode $validationMode = ValidationMode::FILTER;

    protected array $options;

    protected string $queryName;
    protected array $values = [];
    protected Validator $validator;

    protected string $component = 'text';
    protected string $title;

    protected bool $populatingFromScope = false;

    protected ?Model $model = null;

    protected ?Builder $relationQuery = null;

    public function setQueryName(string $queryName): static
    {
        $this->queryName = $queryName;

        return $this;
    }

    public function setMode(FilterMode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function setValidationMode(ValidationMode $validationMode): static
    {
        $this->validationMode = $validationMode;

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setComponent(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function setModel(?Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function populate(string|array|null $values): static
    {
        $this->setValues(Arr::wrap($values));

        return $this;
    }

    /**
     * @internal Used by the model scopes when the value has already been selected
     * from the complete filter payload.
     */
    public function populateFromScope(string|array|null $values): static
    {
        $this->populatingFromScope = true;

        try {
            return $this->populate($values);
        } finally {
            $this->populatingFromScope = false;
        }
    }

    public function queryName(): string
    {
        $this->queryName ??= Str::snake(class_basename(static::class));

        return $this->queryName;
    }

    public function getValue(?string $key = null): mixed
    {
        return $this->values[$key ?? $this->queryName()] ?? null;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function component(): string
    {
        return config('model-filter.filter_component_prefix') . $this->component;
    }

    public function title(): string
    {
        $this->title ??= ucwords(str_replace('_', ' ', Str::snake(class_basename($this))));

        return $this->title;
    }

    public function applicable(): bool
    {
        return true;
    }

    public function visible(): bool
    {
        return true;
    }

    /**
     * @param  Builder<TModel> $query
     *
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        if (! $this->shouldApply()) {
            return $query;
        }

        return $this->applyFilter($query);
    }

    /**
     * @param  Builder<TModel> $query
     *
     * @return Builder<TModel>
     */
    public function applyFilter(Builder $query): Builder
    {
        return $query;
    }

    public function options(): array
    {
        return $this->options ?? [];
    }

    public function model(): ?Model
    {
        return $this->model;
    }

    public function rules(): array
    {
        return [];
    }

    public function fails(): bool
    {
        $this->validator ??= $this->createValidator();

        return $this->validator->fails();
    }

    public function validate(): array
    {
        $this->validator ??= $this->createValidator();

        return $this->validator->validate();
    }

    protected function createValidator(): Validator
    {
        return $this->createValidatorForRules($this->rules());
    }

    protected function hasFilterValue(): bool
    {
        return true;
    }

    protected function shouldApply(): bool
    {
        if (! $this->hasFilterValue()) {
            return false;
        }

        $validator = $this->createValidatorForRules([]);

        if ($this->validationMode === ValidationMode::THROW) {
            $validator->validate();

            return true;
        }

        return ! $validator->fails();
    }

    protected function setValues(array $values): void
    {
        $this->values = $values;
        unset($this->validator);
    }

    protected function validateInputShape(Validator $validator): void
    {
        //
    }

    protected function addInputShapeError(Validator $validator, string $attribute): void
    {
        $validator->addFailure($attribute, 'InputShape');
    }

    protected function isScalarInput(mixed $value): bool
    {
        return is_null($value) || is_scalar($value);
    }

    protected function createValidatorForRules(array $rules): Validator
    {
        $validator = ValidatorFacade::make(
            $this->values,
            $rules,
            $this->getMessages(),
            $this->getValidationAttributes()
        );
        $validator->setFallbackMessages([
            'input_shape' => trans('model-filter::validation.input_shape'),
        ]);

        $validator->after(fn (Validator $validator) => $this->validateInputShape($validator));

        return $validator;
    }

    protected function getMessages()
    {
        return match (true) {
            method_exists($this, 'messages') => $this->messages(),
            default => $this->messages,
        };
    }

    protected function getValidationAttributes()
    {
        return match (true) {
            method_exists($this, 'validationAttributes') => $this->validationAttributes(),
            default => $this->validationAttributes,
        };
    }
}
