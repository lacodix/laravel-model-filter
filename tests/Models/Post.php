<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
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
            'created_at_lower_filter' => new DateFilter('created_at', FilterMode::LOWER_OR_EQUAL),
            'created_at_greater_filter' => new DateFilter('created_at', FilterMode::GREATER_OR_EQUAL),
            'created_at_between' => new DateFilter('created_at', FilterMode::BETWEEN),
            'created_at_between_exclusive' => new DateFilter('created_at', FilterMode::BETWEEN_EXCLUSIVE),
            'created_at_not_between' => new DateFilter('created_at', FilterMode::NOT_BETWEEN),
            'created_at_not_between_inclusive' => new DateFilter('created_at', FilterMode::NOT_BETWEEN_INCLUSIVE),
            'created_at_today' => new DateFilter('created_at'),
            new TypeFilter(),
            'starts_with' => new StringFilter('title', FilterMode::STARTS_WITH),
            'ends_with' => new StringFilter('title', FilterMode::ENDS_WITH),
            'contains' => new StringFilter('title', FilterMode::LIKE),
            'equals' => new StringFilter('title', FilterMode::EQUAL),
            'boolfilter' => new BooleanFilter(['published']),
            new IndividualFilter(),
            'counter_lower_filter' => new NumericFilter('counter', FilterMode::LOWER_OR_EQUAL),
            'counter_greater_filter' => new NumericFilter('counter', FilterMode::GREATER_OR_EQUAL),
            'counter_between' => new NumericFilter('counter', FilterMode::BETWEEN),
            'counter_between_exclusive' => new NumericFilter('counter', FilterMode::BETWEEN_EXCLUSIVE),
            'counter_not_between' => new NumericFilter('counter', FilterMode::NOT_BETWEEN),
            'counter_not_between_inclusive' => new NumericFilter('counter', FilterMode::NOT_BETWEEN_INCLUSIVE),
            'counter_exact' => new NumericFilter('counter'),
        ]);
    }
}
