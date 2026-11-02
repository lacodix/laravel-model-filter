<?php

declare(strict_types=1);

namespace Lacodix\LaravelModelFilter\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trusted model-owned predicates. Mutate and return the supplied builder; do not
 * execute it, replace it or remove existing constraints/scopes. The package groups
 * each custom field predicate and ORs it with other searchable fields, including
 * inside scoped relation queries. Bind values and use only approved column mappings.
 */
interface SearchStrategy
{
    /**
     * Add predicates to the supplied builder using the qualified logical field and bound search values.
     */
    public function applyQuery(Builder $query, string $field, string $search): Builder;

    /**
     * Add predicates treating wildcard characters as ordinary input.
     */
    public function applyLiteralQuery(Builder $query, string $field, string $search): Builder;
}
