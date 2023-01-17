<?php

namespace Lacodix\LaravelModelFilter\Commands;

use Illuminate\Console\Command;

class MakeFilterCommand extends Command
{
    protected $signature = 'make:filter';

    protected $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
