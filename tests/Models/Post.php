<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Tests\Filters\CounterFilter;
use Tests\Filters\IndividualFilter;
use Tests\Filters\TypeFilter;

class Post extends Model
{
    use HasFactory;
    use HasFilters;
    use IsSearchable;

    protected $guarded = [];

    protected array $searchable = [
        'title',
        'content',
    ];

    public function filters(): Collection
    {
        return collect([
            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_lower_filter')))
                ->queryName('created_at_lower_filter')
                ->mode(FilterMode::LOWER_OR_EQUAL),

            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_greater_filter')))
                ->queryName('created_at_greater_filter')
                ->mode(FilterMode::GREATER_OR_EQUAL),

            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_between')))
                ->queryName('created_at_between')
                ->mode(FilterMode::BETWEEN),

            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_between_exclusive')))
                ->queryName('created_at_between_exclusive')
                ->mode(FilterMode::BETWEEN_EXCLUSIVE),

            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_not_between')))
                ->queryName('created_at_not_between')
                ->mode(FilterMode::NOT_BETWEEN),

            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_not_between_inclusive')))
                ->queryName('created_at_not_between_inclusive')
                ->mode(FilterMode::NOT_BETWEEN_INCLUSIVE),

            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_today')))
                ->queryName('created_at_today'),

            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_greater_filter_throws')))
                ->queryName('created_at_greater_filter_throws')
                ->mode(FilterMode::GREATER_OR_EQUAL)
                ->validationMode(ValidationMode::THROW),

            (new DateFilter('created_at'))
                ->title(ucwords(str_replace('_', ' ', 'created_at_between_throws')))
                ->queryName('created_at_between_throws')
                ->mode(FilterMode::BETWEEN)
                ->validationMode(ValidationMode::THROW),

            new TypeFilter(),

            (new TypeFilter())
                ->title(ucwords(str_replace('_', ' ', 'type_filter_throws')))
                ->queryName('type_filter_throws')
                ->validationMode(ValidationMode::THROW),

            (new StringFilter('title'))
                ->title(ucwords(str_replace('_', ' ', 'starts_with')))
                ->queryName('starts_with')
                ->mode(FilterMode::STARTS_WITH),

            (new StringFilter('title'))
                ->title(ucwords(str_replace('_', ' ', 'ends_with')))
                ->queryName('ends_with')
                ->mode(FilterMode::ENDS_WITH),

            (new StringFilter('title'))
                ->title(ucwords(str_replace('_', ' ', 'contains')))
                ->queryName('contains')
                ->mode(FilterMode::LIKE),

            (new StringFilter('title'))
                ->title(ucwords(str_replace('_', ' ', 'equals')))
                ->queryName('equals')
                ->mode(FilterMode::EQUAL),

            (new BooleanFilter(['published']))
                ->title(ucwords(str_replace('_', ' ', 'boolfilter')))
                ->queryName('boolfilter'),

            new IndividualFilter(),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_lower_filter')))
                ->queryName('counter_lower_filter')
                ->mode(FilterMode::LOWER_OR_EQUAL),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_greater_filter')))
                ->queryName('counter_greater_filter')
                ->mode(FilterMode::GREATER_OR_EQUAL),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_between')))
                ->queryName('counter_between')
                ->mode(FilterMode::BETWEEN),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_between_exclusive')))
                ->queryName('counter_between_exclusive')
                ->mode(FilterMode::BETWEEN_EXCLUSIVE),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_not_between')))
                ->queryName('counter_not_between')
                ->mode(FilterMode::NOT_BETWEEN),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_not_between_inclusive')))
                ->queryName('counter_not_between_inclusive')
                ->mode(FilterMode::NOT_BETWEEN_INCLUSIVE),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_exact')))
                ->queryName('counter_exact'),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_greater_filter_throws')))
                ->queryName('counter_greater_filter_throws')
                ->mode(FilterMode::GREATER_OR_EQUAL)
                ->validationMode(ValidationMode::THROW),

            (new NumericFilter('counter'))
                ->title(ucwords(str_replace('_', ' ', 'counter_between_throws')))
                ->queryName('counter_between_throws')
                ->mode(FilterMode::BETWEEN)
                ->validationMode(ValidationMode::THROW),

            new CounterFilter(),
        ]);
    }
}
