<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lacodix\LaravelModelFilter\Enums\FilterMode;

abstract class Filter
{
    public array $messages = [];
    public array $validationAttributes = [];

    protected FilterMode $mode = FilterMode::EQUAL;

    protected array $options;

    public function queryName(string|int $key): string
    {
        return is_int($key) ? Str::snake(class_basename(static::class)) : $key;
    }

    abstract public function apply(Builder $query, string|array $values): Builder;

    public function options(): array
    {
        return $this->options ?? [];
    }

    public function rules(): array
    {
        return [];
    }

    protected function validate($data, $rules = null, $messages = [], $attributes = []): array
    {
        [$rules, $messages, $attributes] = $this->getValidationData($rules, $messages, $attributes);

        $validator = Validator::make($data, $rules, $messages, $attributes);

        return $validator->validate();
    }

    protected function getValidationData($rules, $messages, $attributes): array
    {
        $rules = is_null($rules) ? $this->rules() : $rules;
        $messages = empty($messages) ? $this->getMessages() : $messages;
        $attributes = empty($attributes) ? $this->getValidationAttributes() : $attributes;

        return [$rules, $messages, $attributes];
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
