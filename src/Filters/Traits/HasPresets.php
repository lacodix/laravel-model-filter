<?php

namespace Lacodix\LaravelModelFilter\Filters\Traits;

use Illuminate\Support\Arr;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;

/**
 * Presets are pre-configured filter values a user can pick instead of typing values by
 * hand - like "up to 18" or "10000 and more" next to a free from/to input.
 *
 * A preset is nothing but a shortcut: picking one writes its values into the very same
 * filter value the manual inputs write into. Nothing about a preset is stored, neither
 * in the query string nor anywhere else, which keeps filter links stable in their
 * meaning even when the presets themselves change later on.
 *
 * Only views that render presets show them - of the shipped ones that is the numeric
 * filter. Presets on any other filter type stay invisible.
 */
trait HasPresets
{
    /** @var array<int, array{label: string, values: mixed}> */
    protected array $presets;

    /** @var array<int, array{label: string, values: mixed}>|null */
    protected ?array $resolvedPresets = null;

    /**
     * Expects entries of the shape ['label' => string, 'values' => mixed], the type is
     * kept loose on purpose - this is one of the two places malformed presets are caught.
     *
     * @param  array<array-key, mixed>  $presets
     */
    public function setPresets(array $presets): static
    {
        $this->presets = $this->validatePresets($presets);
        $this->resolvedPresets = null;

        return $this;
    }

    /**
     * Final so that overriding it cannot skip the validation - build dynamic presets by
     * overriding definePresets() instead.
     *
     * Resolved once per filter instance, just like options() and queryName() do: a single
     * render asks for the presets several times and dynamically built ones must not be
     * rebuilt and revalidated each time.
     *
     * @return array<int, array{label: string, values: mixed}>
     */
    final public function presets(): array
    {
        return $this->resolvedPresets ??= $this->validatePresets($this->definePresets());
    }

    public function hasPresets(): bool
    {
        return $this->presets() !== [];
    }

    /**
     * Index of the preset the given values belong to, null if they match no preset at
     * all and are therefore custom ones.
     */
    public function activePreset(mixed $values): ?int
    {
        $current = $this->normalizePresetValues($values);

        foreach ($this->presets() as $index => $preset) {
            if ($this->normalizePresetValues($preset['values']) === $current) {
                return $index;
            }
        }

        return null;
    }

    /**
     * What a preset aware frontend has to render as the current selection: 'none' for no
     * values at all, the index of the matching preset, or 'custom' for values that belong
     * to no preset.
     */
    public function presetState(mixed $values): string|int
    {
        if (array_filter($this->normalizePresetValues($values), static fn (string $value) => $value !== '') === []) {
            return 'none';
        }

        return $this->activePreset($values) ?? 'custom';
    }

    /**
     * The place to build presets dynamically, e.g. from a configuration. Overriding this
     * instead of presets() keeps the shape validation in place.
     *
     * @return array<array-key, mixed>
     */
    protected function definePresets(): array
    {
        return $this->presets ?? [];
    }

    /**
     * Presets are entered by hand or built dynamically, so their shape is checked before
     * anybody relies on it - a malformed entry has to fail loudly instead of ending up as
     * an "Undefined array key" somewhere inside a view.
     *
     * @param  array<array-key, mixed>  $presets
     *
     * @return array<int, array{label: string, values: mixed}>
     */
    protected function validatePresets(array $presets): array
    {
        foreach ($presets as $preset) {
            if (! is_array($preset) || ! array_key_exists('label', $preset) || ! array_key_exists('values', $preset)) {
                throw new InvalidArgumentException(
                    'Every preset needs a label and values, e.g. [\'label\' => \'Up to 18\', \'values\' => [\'\', 18]]'
                );
            }
        }

        /** @var array<int, array{label: string, values: mixed}> $presets */
        $presets = array_values($presets);

        return $presets;
    }

    /**
     * Compares presets and current values in one shape, so that a preset written as
     * [19, ''] still matches the ['19', ''] a request delivers.
     *
     * @return array<int, string>
     */
    protected function normalizePresetValues(mixed $values): array
    {
        return array_map(
            static fn (mixed $value) => is_scalar($value) ? (string) $value : '',
            array_values(Arr::wrap($values))
        );
    }
}
