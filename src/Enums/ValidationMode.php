<?php

declare(strict_types=1);

namespace Lacodix\LaravelModelFilter\Enums;

enum ValidationMode
{
    case THROW;
    case FILTER;
}
