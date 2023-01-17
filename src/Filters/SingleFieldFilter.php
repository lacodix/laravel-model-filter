<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;

abstract class SingleFieldFilter extends Filter
{
    protected string $field;

    public function __construct(?string $field = null, ?FilterMode $mode = null)
    {
        if ($field) {
            $this->field = $field;
        }

        if ($mode !== null) {
            $this->mode = $mode;
        }
    }
}
