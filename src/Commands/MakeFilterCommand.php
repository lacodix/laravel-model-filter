<?php

namespace Lacodix\LaravelModelFilter\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeFilterCommand extends GeneratorCommand
{
    protected $signature = 'make:filter
        {name : The name of the filter class. It will be suffixed with Filter if not present. }
        {--t|type= : Allowed types are boolean, date, select and string. To create an individual filter just omit type. }
        {--f|field= : You can add a field name for date, select and string filters. }';

    protected $description = 'Create a new eloquent filter';

    protected function buildClass($name)
    {
        return str_replace(
            '{{ field }}',
            $this->option('field'),
            parent::buildClass($name)
        );
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/' . match (strtolower($this->option('type'))) {
            'boolean' => 'boolean_filter',
            'string' => 'string_filter',
            'select' => 'select_filter',
            'date' => 'date_filter',
            default => 'filter',
        } . '.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Models\Filters';
    }
}
