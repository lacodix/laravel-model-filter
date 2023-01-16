<?php

namespace Lacodix\LaravelFilter\Filters;

use Lacodix\LaravelFilter\Enums\FilterMode;

abstract class SingleFieldFilter extends Filter
{
    protected string $field;

    public function __construct(?string $field = null, ?FilterMode $mode = null)
    {
        if ($field) {
            $this->field = $field;
        }

        if ($mode) {
            $this->mode = $mode;
        }
    }
}