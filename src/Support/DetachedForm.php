<?php

declare(strict_types=1);

namespace Lacodix\LaravelModelFilter\Support;

/**
 * Controls that must never be submitted - like the preset radios of the numeric filter -
 * point their form attribute at the id of this "form": one that no form has, which leaves
 * them without a form owner.
 *
 * A constant on purpose. It only has to be a string no form of the surrounding application
 * carries by accident, which the namespaced prefix takes care of. It is deliberately not
 * derived from anything: computing it per process would make the rendered HTML differ
 * between workers (response caches, ETags, snapshot tests), and deriving it from
 * application configuration would publish a value of that configuration in the markup.
 *
 * Final by intent: the whole "these controls are never submitted" guarantee hangs on this
 * id resolving to nothing, so it is deliberately not extendable.
 */
final class DetachedForm
{
    public const ID = 'lmf-detached-controls';

    public static function id(): string
    {
        return self::ID;
    }
}
