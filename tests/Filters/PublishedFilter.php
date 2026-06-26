<?php

declare(strict_types=1);

namespace Tests\Filters;

use Lacodix\LaravelModelFilter\Filters\OptionFilter;

class PublishedFilter extends OptionFilter
{
    public function options(): array
    {
        return [
            'published',
        ];
    }
}
