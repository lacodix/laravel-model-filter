<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Database\Eloquent\Builder;

trait RunsOnRelation
{
    public function apply(Builder $query): Builder
    {
        // Historically, direct application without a value still produced the
        // relation existence query. Keep that behavior while guarding values.
        if ($this->hasFilterValue() && ! $this->shouldApply()) {
            return $query;
        }

        return $query->whereHas($this->relation, function (Builder $query) {
            $this->relationQuery = $query;

            $result = $this->applyFilter($query);

            $this->relationQuery = null;

            return $result;
        });
    }

    public function runOnRelation(Builder $query, callable $callback): Builder
    {
        return $query->whereHas($this->relation, function (Builder $query) use ($callback) {
            $this->relationQuery = $query;

            $result = $callback($query);

            $this->relationQuery = null;

            return $result;
        });
    }
}
