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
            (new DateFilter('created_at'))->setQueryName('created_at_lower_filter')->mode(FilterMode::LOWER_OR_EQUAL),
            (new DateFilter('created_at'))->setQueryName('created_at_greater_filter')->mode(FilterMode::GREATER_OR_EQUAL),
            (new DateFilter('created_at'))->setQueryName('created_at_between')->mode(FilterMode::BETWEEN),
            (new DateFilter('created_at'))->setQueryName('created_at_between_exclusive')->mode(FilterMode::BETWEEN_EXCLUSIVE),
            (new DateFilter('created_at'))->setQueryName('created_at_not_between')->mode(FilterMode::NOT_BETWEEN),
            (new DateFilter('created_at'))->setQueryName('created_at_not_between_inclusive')->mode(FilterMode::NOT_BETWEEN_INCLUSIVE),
            (new DateFilter('created_at'))->setQueryName('created_at_today'),
            (new DateFilter('created_at'))->setQueryName('created_at_greater_filter_throws')->mode(FilterMode::GREATER_OR_EQUAL)->validationMode(ValidationMode::THROW),
            (new DateFilter('created_at'))->setQueryName('created_at_between_throws')->mode(FilterMode::BETWEEN)->validationMode(ValidationMode::THROW),
            new TypeFilter(),
            (new TypeFilter())->setQueryName('type_filter_throws')->validationMode(ValidationMode::THROW),
            (new StringFilter('title'))->setQueryName('starts_with')->mode(FilterMode::STARTS_WITH),
            (new StringFilter('title'))->setQueryName('ends_with')->mode(FilterMode::ENDS_WITH),
            (new StringFilter('title'))->setQueryName('contains')->mode(FilterMode::LIKE),
            (new StringFilter('title'))->setQueryName('equals')->mode(FilterMode::EQUAL),
            (new BooleanFilter(['published']))->setQueryName('boolfilter'),
            new IndividualFilter(),
            (new NumericFilter('counter'))->setQueryName('counter_lower_filter')->mode(FilterMode::LOWER_OR_EQUAL),
            (new NumericFilter('counter'))->setQueryName('counter_greater_filter')->mode(FilterMode::GREATER_OR_EQUAL),
            (new NumericFilter('counter'))->setQueryName('counter_between')->mode(FilterMode::BETWEEN),
            (new NumericFilter('counter'))->setQueryName('counter_between_exclusive')->mode(FilterMode::BETWEEN_EXCLUSIVE),
            (new NumericFilter('counter'))->setQueryName('counter_not_between')->mode(FilterMode::NOT_BETWEEN),
            (new NumericFilter('counter'))->setQueryName('counter_not_between_inclusive')->mode(FilterMode::NOT_BETWEEN_INCLUSIVE),
            (new NumericFilter('counter'))->setQueryName('counter_exact'),
            (new NumericFilter('counter'))->setQueryName('counter_greater_filter_throws')->mode(FilterMode::GREATER_OR_EQUAL)->validationMode(ValidationMode::THROW),
            (new NumericFilter('counter'))->setQueryName('counter_between_throws')->mode(FilterMode::BETWEEN)->validationMode(ValidationMode::THROW),

        ]);
    }
}
