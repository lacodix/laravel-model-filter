<?php

namespace Lacodix\LaravelModelFilter\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakeFilterCommand extends GeneratorCommand
{
    protected $type = 'Filter';

    protected ?string $filtertype = null;
    protected array $allowedTypes = [
        'filter' => 'Individual Filter',
        'boolean' => 'Boolean',
        'string' => 'String',
        'select' => 'Select',
        'enum' => 'Enum',
        'date' => 'Date',
        'numeric' => 'Numeric',
        'belongs-to' => 'Belongs To',
        'belongs-to-many' => 'Belongs To Many',
        'belongs-to-many-timeframe' => 'Belongs To Many Timeframe',
    ];

    protected ?string $fieldId = null;
    protected array $fitersWithField = [
        'string',
        'select',
        'enum',
        'date',
        'numeric',
        'belongs-to',
        'belongs-to-many',
        'belongs-to-many-timeframe',
    ];

    protected ?string $relationName = null;
    protected ?string $relationTitle = null;
    protected array $fitersWithRelation = [
        'belongs-to',
        'belongs-to-many',
        'belongs-to-many-timeframe',
    ];

    protected $signature = 'make:filter
        {name : The name of the filter class. }
        {--t|type= : For allowed types see documentation or just omit the attribute to get a selection. }
        {--f|field= : You must add a database columns for all filters that directly access database fields. }
        {--relation= : You must add a relation model for relation filters. }
        {--title= : You must add a database column that contains the title for the relations filters. }
        {--start_field= : For the Belongs To Many Timeframe filter you need to specify the start field. }
        {--end_field= : For the Belongs To Many Timeframe filter you need to specify the end field. }';

    protected $description = 'Create a new eloquent filter';

    public function handle()
    {
        $this->filtertype = strtolower($this->option('type'));
        if (! $this->filtertype || ! array_key_exists($this->filtertype, $this->allowedTypes)) {
            $this->filtertype = select(
                label: 'What filter type should be created?',
                options: $this->allowedTypes,
                default: 'filter'
            );
        }

        $this->fieldId = strtolower($this->option('field'));
        if (! $this->fieldId && in_array($this->filtertype, $this->fitersWithField)) {
            $this->fieldId = text('What database table field should be used for this filter?');
        }

        if (in_array($this->filtertype, $this->fitersWithRelation)) {
            $this->relationName = $this->option('relation');
            if (! $this->relationName || ! class_exists($this->relationName)) {
                $this->relationName = search(
                    label: 'What eloquent model should be used for the relation?',
                    options: fn (string $value) => array_filter(
                        [
                            ...$this->getAllModelClasses(),
                            'none' => 'Not listed',
                        ],
                        static fn (string $class) => str_contains(strtolower($class), strtolower($value))
                    ),
                );

                while (! class_exists($this->relationName)) {
                    $this->relationName = text('Please enter the full classname (with namespace) of the relation model');
                }
            }

            $this->relationTitle = strtolower($this->option('title'));
            if ($this->relationTitle === '' || $this->relationTitle === '0') {
                $this->relationTitle = text('What database table column should be used for the title?');
            }
        }

        return parent::handle();
    }

    protected function buildClass($name)
    {
        return str_replace(
            [
                '{{ field }}',
                '{{ start_field }}',
                '{{ end_field }}',
                '{{ relationName }}',
                '{{ relationTitle }}',
            ],
            [
                $this->fieldId,
                $this->option('start_field'),
                $this->option('end_field'),
                $this->relationName,
                $this->relationTitle,
            ],
            parent::buildClass($name)
        );
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/' . match ($this->filtertype) {
            'boolean' => 'boolean_filter',
            'string' => 'string_filter',
            'select' => 'select_filter',
            'enum' => 'enum_filter',
            'date' => 'date_filter',
            'numeric' => 'numeric_filter',
            'belongs-to' => 'belongs_to_filter',
            'belongs-to-many' => 'belongs_to_many_filter',
            'belongs-to-many-timeframe' => 'belongs_to_many_timeframe_filter',
            default => 'filter',
        } . '.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Models\Filters';
    }

    protected function getAllModelClasses(): array
    {
        // Get the path to the Models directory
        $modelsPath = app_path('Models');

        // Get all PHP files within the Models directory
        $modelFiles = glob("{$modelsPath}/*.php");

        // Initialize an empty array to store model class names
        $modelClasses = [];

        // Iterate through each PHP file found
        foreach ($modelFiles as $file) {
            // Extract the class name from the file path
            $className = Str::of(basename((string) $file))->before('.php');

            // Resolve the fully qualified class name
            $fqcn = "\\App\\Models\\{$className}";

            // Check if the class exists and if it's indeed a class
            if (class_exists($fqcn) && is_subclass_of($fqcn, \Illuminate\Database\Eloquent\Model::class)) {
                // Add the class name to the array
                $modelClasses[$fqcn] = $fqcn;
            }
        }

        return $modelClasses;
    }
}
