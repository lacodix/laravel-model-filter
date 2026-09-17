<?php

namespace Lacodix\LaravelModelFilter\Filters\Traits;

use BackedEnum;
use Closure;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;

/**
 * Option metadata is one free array per option value - a picture, initials, a
 * colour, a subtitle, whatever a consumer rendering the options wants to know
 * about them. Like Filter::meta() the package stores it and never interprets
 * it: filtering, validation and the query string know nothing about it.
 *
 * The map is keyed by the option values as options() returns them, not by
 * their labels, so that it survives translated or re-labelled options. Options
 * are often database-backed, so the map may be a closure that is resolved
 * lazily - once per filter instance - the first time somebody asks for it.
 */
trait HasOptionMeta
{
    /** @var array<array-key, array<string, mixed>>|Closure|null */
    protected array|Closure|null $optionMeta = null;

    /** @var array<array-key, array<string, mixed>>|null */
    protected ?array $resolvedOptionMeta = null;

    /**
     * Set the whole map at once, either as `[value => [...meta]]` or as a
     * closure returning it (called with the filter, so that it can read the
     * resolved options()). A repeated call replaces the previous map.
     *
     * @param  array<array-key, array<string, mixed>>|Closure  $meta
     */
    public function optionMeta(array|Closure $meta): static
    {
        $this->optionMeta = $meta;
        $this->resolvedOptionMeta = null;

        return $this;
    }

    /**
     * Final so that overriding it cannot skip the shape check - build dynamic
     * option meta by overriding defineOptionMeta() instead.
     *
     * @return array<array-key, array<string, mixed>>
     */
    final public function getOptionMeta(): array
    {
        return $this->resolvedOptionMeta ??= $this->validateOptionMeta($this->defineOptionMeta());
    }

    /**
     * The meta of one option value; an empty array for values that carry none.
     * Values are matched by their string form, so an integer id and the
     * numeric string a request delivers address the same entry; a backed enum
     * case addresses the entry of its value.
     *
     * @return array<string, mixed>
     */
    public function optionMetaFor(mixed $value): array
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if (! is_scalar($value)) {
            return [];
        }

        $meta = $this->getOptionMeta();
        $key = (string) $value;

        if (array_key_exists($key, $meta)) {
            return $meta[$key];
        }

        foreach ($meta as $optionValue => $optionMeta) {
            if ((string) $optionValue === $key) {
                return $optionMeta;
            }
        }

        return [];
    }

    /**
     * The place to build option meta dynamically in a filter class. Overriding
     * this instead of getOptionMeta() keeps the shape check in place; like
     * definePresets() it takes over entirely, so a fluently set map is only
     * honoured when the override includes parent::defineOptionMeta().
     *
     * @return array<array-key, mixed>
     */
    protected function defineOptionMeta(): array
    {
        if ($this->optionMeta instanceof Closure) {
            return ($this->optionMeta)($this);
        }

        return $this->optionMeta ?? [];
    }

    /**
     * @param  array<array-key, mixed>  $meta
     *
     * @return array<array-key, array<string, mixed>>
     */
    protected function validateOptionMeta(array $meta): array
    {
        foreach ($meta as $value => $entry) {
            if (! is_array($entry)) {
                throw new InvalidArgumentException(
                    "Option meta must be an array per option value, e.g. ['{$value}' => ['avatar' => '...']]"
                );
            }
        }

        /** @var array<array-key, array<string, mixed>> $meta */
        return $meta;
    }
}
