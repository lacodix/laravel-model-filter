<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Validator;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;

abstract class Filter
{
    use Macroable;
    use Conditionable;

    public array $messages = [];
    public array $validationAttributes = [];

    public FilterMode $mode = FilterMode::EQUAL;
    public ValidationMode $validationMode = ValidationMode::FILTER;

    protected array $options;

    protected string $queryName;
    protected array $values;
    protected Validator $validator;

    protected string $component = 'text';
    protected string $title;

    public function setQueryName(string $queryName): self
    {
        $this->queryName = $queryName;

        return $this;
    }

    public function setMode(FilterMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function setValidationMode(ValidationMode $validationMode): self
    {
        $this->validationMode = $validationMode;

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function populate(string|array $values): self
    {
        $this->values = Arr::wrap($values);

        return $this;
    }

    public function queryName(): string
    {
        $this->queryName ??= Str::snake(class_basename(static::class));

        return $this->queryName;
    }

    public function component(): string
    {
        return 'lacodix-filter::filters.' . $this->component;
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

    abstract public function apply(Builder $query): Builder;

    public function options(): array
    {
        return $this->options ?? [];
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
        return ValidatorFacade::make(
            $this->values,
            $this->rules(),
            $this->getMessages(),
            $this->getValidationAttributes()
        );
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
