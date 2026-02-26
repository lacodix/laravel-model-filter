<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends SelectFilter<TModel>
 */
class EnumFilter extends SelectFilter
{
    protected string $enum;
    protected string $translationPrefix = '';
    protected bool $useNameForTranslation = false;

    public function options(): array
    {
        $field = $this->useNameForTranslation ? 'name' : 'value';

        return collect($this->enum::cases())
            ->mapWithKeys(fn ($case) => [trans($this->translationPrefix . $case->{$field}) => $case->value])
            ->all();
    }

    public function setEnum(string $enum): static
    {
        $this->enum = $enum;

        return $this;
    }

    public function setTranslationPrefix(string $translationPrefix, bool $useNameForTranslation = false): static
    {
        $this->translationPrefix = $translationPrefix;
        $this->useNameForTranslation = $useNameForTranslation;

        return $this;
    }
}
