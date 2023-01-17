<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Tests\Filters\IndividualFilter;
use Tests\Filters\TypeFilter;

class Post extends Model
{
    use HasFactory;
    use HasFilters;

    protected $guarded = [];

    public function filters(): Collection
    {
        return collect([
            'created_at_lower_filter' => new DateFilter('created_at', FilterMode::LOWER),
            'created_at_greater_filter' => new DateFilter('created_at', FilterMode::GREATER),
            new TypeFilter(),
            'starts_with' => new StringFilter('title', FilterMode::STARTS_WITH),
            'ends_with' => new StringFilter('title', FilterMode::ENDS_WITH),
            'contains' => new StringFilter('title', FilterMode::LIKE),
            'equals' => new StringFilter('title', FilterMode::EQUAL),
            'boolfilter' => new BooleanFilter(['published']),
            new IndividualFilter(),
        ]);
    }
}
