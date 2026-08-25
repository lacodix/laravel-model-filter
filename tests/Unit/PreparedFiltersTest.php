<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;
use Lacodix\LaravelModelFilter\Exceptions\UnknownFilterGroupException;
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Lacodix\LaravelModelFilter\Filters\Filter;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Support\FilterPreparation;
use Lacodix\LaravelModelFilter\Support\PreparedFilters;
use Lacodix\LaravelModelFilter\Traits\RunsOnRelation;
use Tests\Models\Shape\InputContractPost;
use Tests\Support\InputContractTestSupport;

beforeEach(function () {
    Filter::flushMacros();
    InputContractPost::$configuredFilters = [];
    PreparationInapplicableFilter::$populations = 0;
    PreparationLifecycleFilter::$events = [];
});

afterEach(function () {
    Filter::flushMacros();
});

it('treats null and empty strings as absent prepared values', function (mixed $value) {
    InputContractPost::$configuredFilters = [
        (new StringFilter('title'))
            ->setQueryName('title_filter')
            ->setValidationMode(ValidationMode::THROW),
    ];

    $prepared = (new FilterPreparation)->prepare(
        new InputContractPost,
        ['title_filter' => $value],
        FilterPreparation::DEFAULT_GROUP,
    );

    expect($prepared)
        ->toBeInstanceOf(PreparedFilters::class)
        ->isEmpty()->toBeTrue()
        ->count()->toBe(0)
        ->queryNames()->toBe([])
        ->all()->toBe([])
        ->and(count($prepared))->toBe(0)
        ->and(iterator_to_array($prepared))->toBe([]);
})->with([
    'null' => null,
    'empty string' => '',
]);

it('keeps false and zero while ignoring unknown query names', function () {
    InputContractPost::$configuredFilters = [
        (new BooleanFilter('published'))->setQueryName('false_filter'),
        (new NumericFilter('counter'))->setQueryName('zero_filter'),
    ];

    $prepared = (new FilterPreparation)->prepare(
        new InputContractPost,
        [
            'unknown_filter' => 'ignored',
            'false_filter' => false,
            'zero_filter' => 0,
        ],
        FilterPreparation::DEFAULT_GROUP,
    );

    expect($prepared->isEmpty())->toBeFalse()
        ->and($prepared->count())->toBe(2)
        ->and($prepared->queryNames())->toBe(['false_filter', 'zero_filter'])
        ->and($prepared->all()[0]->getValue())->toBe('')
        ->and($prepared->all()[1]->getValue())->toBe('0')
        ->and(iterator_to_array($prepared))->toBe($prepared->all());
});

it('does not prepare invalid values or invalid input shapes in filter mode', function (mixed $value) {
    InputContractPost::$configuredFilters = [
        (new SelectFilter('type'))
            ->setOptions(['Page' => 'page'])
            ->setQueryName('type_filter')
            ->setValidationMode(ValidationMode::FILTER),
    ];

    $prepared = (new FilterPreparation)->prepare(
        new InputContractPost,
        ['type_filter' => $value],
        FilterPreparation::DEFAULT_GROUP,
    );

    expect($prepared->isEmpty())->toBeTrue();
})->with([
    'invalid option' => 'unknown',
    'invalid nested shape' => [['page']],
]);

it('throws for invalid values and input shapes in throw mode', function (mixed $value) {
    InputContractPost::$configuredFilters = [
        (new SelectFilter('type'))
            ->setOptions(['Page' => 'page'])
            ->setQueryName('type_filter')
            ->setValidationMode(ValidationMode::THROW),
    ];

    expect(fn () => (new FilterPreparation)->prepare(
        new InputContractPost,
        ['type_filter' => $value],
        FilterPreparation::DEFAULT_GROUP,
    ))->toThrow(ValidationException::class);
})->with([
    'invalid option' => 'unknown',
    'invalid nested shape' => [['page']],
]);

it('normalizes scalar multi-select values like the existing scope', function () {
    InputContractPost::$configuredFilters = [
        (new SelectFilter('type'))
            ->setOptions(['Page' => 'page', 'Post' => 'post'])
            ->setMode(FilterMode::CONTAINS)
            ->setQueryName('type_filter'),
    ];

    $prepared = (new FilterPreparation)->prepare(
        new InputContractPost,
        ['type_filter' => 'page'],
        FilterPreparation::DEFAULT_GROUP,
    );

    expect($prepared->queryNames())->toBe(['type_filter'])
        ->and($prepared->all()[0]->getValue())->toBe(['page']);
});

it('does not populate or prepare filters that are not applicable', function () {
    InputContractPost::$configuredFilters = [
        (new PreparationInapplicableFilter('title'))->setQueryName('title_filter'),
    ];

    $prepared = (new FilterPreparation)->prepare(
        new InputContractPost,
        ['title_filter' => 'needle'],
        FilterPreparation::DEFAULT_GROUP,
    );

    expect($prepared->isEmpty())->toBeTrue()
        ->and(PreparationInapplicableFilter::$populations)->toBe(0);
});

it('does not prepare a relation filter without an applicable filter value or change a query', function () {
    InputContractPost::$configuredFilters = [
        (new PreparationNotReadyRelationFilter)->setQueryName('relation_filter'),
    ];
    $query = InputContractPost::query();
    $before = InputContractTestSupport::snapshot($query);

    $prepared = (new FilterPreparation)->prepare(
        new InputContractPost,
        ['relation_filter' => 'needle'],
        FilterPreparation::DEFAULT_GROUP,
    );

    expect($prepared->isEmpty())->toBeTrue()
        ->and(InputContractTestSupport::snapshot($query))->toBe($before);
});

it('rejects unknown groups in strict mode while legacy resolution still falls back', function () {
    InputContractPost::$configuredFilters = [
        FilterPreparation::DEFAULT_GROUP => [
            (new StringFilter('title'))->setQueryName('fallback_filter'),
        ],
        'known' => [
            (new NumericFilter('counter'))->setQueryName('known_filter'),
        ],
    ];
    $model = new InputContractPost;
    $preparation = new FilterPreparation;

    expect(fn () => $preparation->prepare(
        $model,
        ['fallback_filter' => 'needle'],
        'missing',
        strictGroup: true,
    ))->toThrow(
        UnknownFilterGroupException::class,
        'Filter group [missing] is not defined for model ['.InputContractPost::class.'].',
    );

    $preparedWithLegacyFallback = $preparation->prepare(
        $model,
        ['fallback_filter' => 'needle'],
        'missing',
    );

    expect($preparedWithLegacyFallback->queryNames())->toBe(['fallback_filter'])
        ->and($model->filterInstances('missing')
            ->map(static fn (Filter $filter): string => $filter->queryName())
            ->all())->toBe(['fallback_filter']);

    $legacyQuery = InputContractPost::query()->filter(
        ['fallback_filter' => 'needle'],
        'missing',
    );

    expect(strtolower((string) $legacyQuery->toSql()))->toContain('where');
});

it('rejects a named strict group for an ungrouped filter list', function () {
    InputContractPost::$configuredFilters = [
        (new StringFilter('title'))->setQueryName('title_filter'),
    ];
    $model = new InputContractPost;
    $preparation = new FilterPreparation;

    expect(fn () => $preparation->prepare(
        $model,
        ['title_filter' => 'needle'],
        'missing',
        strictGroup: true,
    ))->toThrow(
        UnknownFilterGroupException::class,
        'Filter group [missing] is not defined for model ['.InputContractPost::class.'].',
    );

    expect($preparation->prepare(
        $model,
        ['title_filter' => 'needle'],
        'missing',
    )->queryNames())->toBe(['title_filter'])
        ->and($model->filterInstances('missing')
            ->map(static fn (Filter $filter): string => $filter->queryName())
            ->all())->toBe(['title_filter']);
});

it('rejects models without a filters method', function () {
    $model = new class extends Model {};

    expect(fn () => (new FilterPreparation)->prepare(
        $model,
        [],
        FilterPreparation::DEFAULT_GROUP,
    ))->toThrow(InvalidArgumentException::class, 'does not expose filters().');
});

it('rejects models whose filters method does not return an array', function () {
    $model = new class extends Model {
        public function filters(): mixed
        {
            return collect();
        }
    };

    expect(fn () => (new FilterPreparation)->prepare(
        $model,
        [],
        FilterPreparation::DEFAULT_GROUP,
    ))->toThrow(InvalidArgumentException::class, 'must return an array from filters().');
});

it('rejects non-filter results from the mapFilter macro', function () {
    InputContractPost::$configuredFilters = [
        (new StringFilter('title'))->setQueryName('title_filter'),
    ];
    Filter::macro('mapFilter', fn (Model $model): string => $model::class);

    expect(fn () => (new FilterPreparation)->prepare(
        new InputContractPost,
        ['title_filter' => 'needle'],
        FilterPreparation::DEFAULT_GROUP,
    ))->toThrow(InvalidArgumentException::class, 'The mapFilter macro must return a filter instance.');
});

it('configures and binds a distinct mapFilter replacement', function () {
    InputContractPost::$configuredFilters = [
        (new StringFilter('title'))->setQueryName('title_filter'),
    ];
    Filter::macro('mapFilter', fn (Model $model): Filter => (new StringFilter('title'))
        ->setQueryName('title_filter')
        ->setTitle('Mapped for '.$model::class));
    $model = new InputContractPost;
    $configuredInstances = [];

    $prepared = (new FilterPreparation)->prepare(
        $model,
        ['title_filter' => 'needle'],
        FilterPreparation::DEFAULT_GROUP,
        configure: static function (Filter $filter) use (&$configuredInstances): void {
            $configuredInstances[] = $filter;
            $filter->setTitle('CONFIGURED');
        },
    );
    $preparedFilter = $prepared->all()[0];

    expect($configuredInstances)->toHaveCount(2)
        ->and($configuredInstances[0])->not->toBe($configuredInstances[1])
        ->and($preparedFilter)->toBe($configuredInstances[1])
        ->and($preparedFilter->title())->toBe('CONFIGURED')
        ->and($preparedFilter->model())->toBe($model)
        ->and($preparedFilter->getValue())->toBe('needle');
});

it('uses fresh objects and keeps prepared population away from UI instances', function () {
    $definition = (new SelectFilter('type'))
        ->setOptions(['Page' => 'page', 'Post' => 'post'])
        ->setQueryName('type_filter');
    InputContractPost::$configuredFilters = [$definition];
    $model = new InputContractPost;
    $uiFilter = $model->filterInstances()->first();
    $uiFilter->populateFromScope('unknown');
    expect($uiFilter->fails())->toBeTrue();

    $preparation = new FilterPreparation;
    $configurationState = [];

    $prepared = $preparation->prepare(
        $model,
        ['type_filter' => 'page'],
        FilterPreparation::DEFAULT_GROUP,
        configure: static function (Filter $filter) use (&$configurationState): void {
            $configurationState[] = [
                'values' => $filter->getValues(),
                'fails' => $filter->fails(),
            ];
        },
    );
    $preparedAgain = $preparation->prepare(
        $model,
        ['type_filter' => 'post'],
        FilterPreparation::DEFAULT_GROUP,
    );

    expect($prepared->all()[0])->not->toBe($uiFilter)
        ->and($preparedAgain->all()[0])->not->toBe($prepared->all()[0])
        ->and($prepared->all()[0]->getValue())->toBe('page')
        ->and($preparedAgain->all()[0]->getValue())->toBe('post')
        ->and($uiFilter->getValue())->toBe('unknown')
        ->and($uiFilter->fails())->toBeTrue()
        ->and($configurationState)->toBe([
            ['values' => [], 'fails' => false],
        ]);
});

it('configures every fresh instance before visibility options population and validation', function () {
    InputContractPost::$configuredFilters = [
        (new PreparationLifecycleFilter('type'))->setQueryName('configured_filter'),
        (new PreparationLifecycleFilter('type'))->setQueryName('unused_filter'),
    ];

    $prepared = (new FilterPreparation)->prepare(
        new InputContractPost,
        ['configured_filter' => 'page'],
        FilterPreparation::DEFAULT_GROUP,
        configure: static function (Filter $filter): void {
            if (! $filter instanceof PreparationLifecycleFilter) {
                throw new LogicException('Unexpected filter type.');
            }

            $filter->configured = true;
            $filter->setOptions(['Page' => 'page']);
            PreparationLifecycleFilter::$events[] = 'configure:'.$filter->queryName();
        },
    );

    expect($prepared->queryNames())->toBe(['configured_filter'])
        ->and(PreparationLifecycleFilter::$events)->toBe([
            'configure:configured_filter',
            'configure:unused_filter',
            'visible:configured_filter',
            'visible:unused_filter',
            'applicable:configured_filter',
            'populate:configured_filter',
            'options:configured_filter',
            'rules:configured_filter',
        ]);
});

final class PreparationInapplicableFilter extends StringFilter
{
    public static int $populations = 0;

    public function applicable(): bool
    {
        return false;
    }

    public function populate(string|array|null $values): static
    {
        self::$populations++;

        return parent::populate($values);
    }
}

final class PreparationNotReadyRelationFilter extends StringFilter
{
    use RunsOnRelation;

    protected string $relation = 'tags';
    protected string $field = 'title';

    protected function hasFilterValue(): bool
    {
        return false;
    }
}

final class PreparationLifecycleFilter extends SelectFilter
{
    /** @var list<string> */
    public static array $events = [];

    public bool $configured = false;

    public function visible(): bool
    {
        $this->record('visible');

        return true;
    }

    public function applicable(): bool
    {
        $this->record('applicable');

        return true;
    }

    public function populate(string|array|null $values): static
    {
        $this->record('populate');

        return parent::populate($values);
    }

    public function options(): array
    {
        $this->record('options');

        return parent::options();
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $this->record('rules');

        return $rules;
    }

    private function record(string $event): void
    {
        if (! $this->configured) {
            throw new LogicException($event.' ran before configuration.');
        }

        self::$events[] = $event.':'.$this->queryName();
    }
}
