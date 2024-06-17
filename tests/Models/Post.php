<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\TimeframeFilterPrecision;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Lacodix\LaravelModelFilter\Traits\IsSortable;
use Tests\Filters\CounterFilter;
use Tests\Filters\IndividualFilter;
use Tests\Filters\TagFilter;
use Tests\Filters\TagTimeframeFilter;
use Tests\Filters\TypeFilter;

class Post extends Model
{
    use HasFactory;
    use HasFilters;
    use IsSearchable;
    use IsSortable;

    protected $guarded = [];

    protected array $searchable = [
        'title',
        'content',
    ];

    protected array $sortable = [
        'title',
        'created_at',
        'counter',
    ];

    public function filters(): array
    {
        return [
            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_lower_filter')))
                ->setQueryName('created_at_lower_filter')
                ->setMode(FilterMode::LOWER_OR_EQUAL),

            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_greater_filter')))
                ->setQueryName('created_at_greater_filter')
                ->setMode(FilterMode::GREATER_OR_EQUAL),

            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_between')))
                ->setQueryName('created_at_between')
                ->setMode(FilterMode::BETWEEN),

            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_between_exclusive')))
                ->setQueryName('created_at_between_exclusive')
                ->setMode(FilterMode::BETWEEN_EXCLUSIVE),

            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_not_between')))
                ->setQueryName('created_at_not_between')
                ->setMode(FilterMode::NOT_BETWEEN),

            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_not_between_inclusive')))
                ->setQueryName('created_at_not_between_inclusive')
                ->setMode(FilterMode::NOT_BETWEEN_INCLUSIVE),

            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_today')))
                ->setQueryName('created_at_today'),

            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_greater_filter_throws')))
                ->setQueryName('created_at_greater_filter_throws')
                ->setMode(FilterMode::GREATER_OR_EQUAL)
                ->setValidationMode(ValidationMode::THROW),

            (new DateFilter('created_at'))
                ->setTitle(ucwords(str_replace('_', ' ', 'created_at_between_throws')))
                ->setQueryName('created_at_between_throws')
                ->setMode(FilterMode::BETWEEN)
                ->setValidationMode(ValidationMode::THROW),

            new TypeFilter(),

            (new TypeFilter())
                ->setTitle(ucwords(str_replace('_', ' ', 'type_filter_throws')))
                ->setQueryName('type_filter_throws')
                ->setValidationMode(ValidationMode::THROW),

            (new TypeFilter())
                ->setTitle(ucwords(str_replace('_', ' ', 'type_multi')))
                ->setQueryName('type_multi')
                ->setMode(FilterMode::CONTAINS),

            (new StringFilter('title'))
                ->setTitle(ucwords(str_replace('_', ' ', 'starts_with')))
                ->setQueryName('starts_with')
                ->setMode(FilterMode::STARTS_WITH),

            (new StringFilter('title'))
                ->setTitle(ucwords(str_replace('_', ' ', 'ends_with')))
                ->setQueryName('ends_with')
                ->setMode(FilterMode::ENDS_WITH),

            (new StringFilter('title'))
                ->setTitle(ucwords(str_replace('_', ' ', 'contains')))
                ->setQueryName('contains')
                ->setMode(FilterMode::LIKE),

            (new StringFilter('title'))
                ->setTitle(ucwords(str_replace('_', ' ', 'equals')))
                ->setQueryName('equals')
                ->setMode(FilterMode::EQUAL),

            (new BooleanFilter(['published']))
                ->setTitle(ucwords(str_replace('_', ' ', 'boolfilter')))
                ->setQueryName('boolfilter'),

            new IndividualFilter(),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_lower_filter')))
                ->setQueryName('counter_lower_filter')
                ->setMode(FilterMode::LOWER_OR_EQUAL),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_greater_filter')))
                ->setQueryName('counter_greater_filter')
                ->setMode(FilterMode::GREATER_OR_EQUAL),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_between')))
                ->setQueryName('counter_between')
                ->setMode(FilterMode::BETWEEN),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_between_exclusive')))
                ->setQueryName('counter_between_exclusive')
                ->setMode(FilterMode::BETWEEN_EXCLUSIVE),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_not_between')))
                ->setQueryName('counter_not_between')
                ->setMode(FilterMode::NOT_BETWEEN),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_not_between_inclusive')))
                ->setQueryName('counter_not_between_inclusive')
                ->setMode(FilterMode::NOT_BETWEEN_INCLUSIVE),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_exact')))
                ->setQueryName('counter_exact'),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_greater_filter_throws')))
                ->setQueryName('counter_greater_filter_throws')
                ->setMode(FilterMode::GREATER_OR_EQUAL)
                ->setValidationMode(ValidationMode::THROW),

            (new NumericFilter('counter'))
                ->setTitle(ucwords(str_replace('_', ' ', 'counter_between_throws')))
                ->setQueryName('counter_between_throws')
                ->setMode(FilterMode::BETWEEN)
                ->setValidationMode(ValidationMode::THROW),

            new CounterFilter(),

            new TagFilter(),

            (new TagFilter())
                ->setTitle(ucwords(str_replace('_', ' ', 'tag_filter_contains')))
                ->setQueryName('tag_filter_contains')
                ->setMode(FilterMode::CONTAINS),

            new TagTimeframeFilter(),

            (new TagTimeframeFilter())
                ->setTitle(ucwords(str_replace('_', ' ', 'tag_timeframe_filter_contains')))
                ->setQueryName('tag_timeframe_filter_contains')
                ->setMode(FilterMode::CONTAINS),

            (new TagTimeframeFilter())
                ->setTitle(ucwords(str_replace('_', ' ', 'tag_timeframe_filter_day')))
                ->setQueryName('tag_timeframe_filter_day')
                ->setPrecision(TimeframeFilterPrecision::DAY),

            (new TagTimeframeFilter())
                ->setTitle(ucwords(str_replace('_', ' ', 'tag_timeframe_filter_year')))
                ->setQueryName('tag_timeframe_filter_year')
                ->setPrecision(TimeframeFilterPrecision::YEAR),
        ];
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withPivot(['start', 'end']);
    }
}
