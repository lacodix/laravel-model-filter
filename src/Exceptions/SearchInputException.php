<?php

declare(strict_types=1);

namespace Lacodix\LaravelModelFilter\Exceptions;

class SearchInputException extends \RuntimeException
{
    public static function limitExceeded(): self
    {
        return new self('The search input exceeds a configured limit.');
    }
}
