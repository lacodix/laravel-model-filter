<?php

namespace Lacodix\LaravelModelFilter\Filters;

class EnumFilter extends SelectFilter
{
    protected string $enum;
    protected string $translationPrefix = '';

    public function options(): array
    {
        return collect($this->enum::cases())
            ->mapWithKeys(fn ($case) => [trans($this->translationPrefix . $case->value) => $case->value])
            ->all();
    }

    public function setEnum(string $enum): static
    {
        $this->enum = $enum;

        return $this;
    }

    public function setTranslationPrefix(string $translationPrefix): static
    {
        $this->translationPrefix = $translationPrefix;

        return $this;
    }
}
