<?php

namespace Lacodix\LaravelFilter\Commands;

use Illuminate\Console\Command;

class MakeFilterCommand extends Command
{
    public $signature = 'make:filter';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
