<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSortable;
use Tests\Filters\CounterFilter;
use Tests\Filters\PublishedFilter;

class Comment extends Model
{
    use HasFactory;
    use HasFilters;
    use IsSortable;

    protected $filters = [
        'frontend' => [
            PublishedFilter::class,
        ],
        'backend' => [
            CounterFilter::class,
        ],
    ];

    protected array $sortable = [
        'title' => 'desc',
        'created_at',
        'counter',
    ];

    protected $guarded = [];
}
