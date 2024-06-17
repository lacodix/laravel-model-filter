<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSortable;
use Tests\Filters\CounterFilter;
use Tests\Filters\PostFilter;
use Tests\Filters\PublishedFilter;

class Comment extends Model
{
    use HasFactory;
    use HasFilters;
    use IsSortable;

    protected array $sortable = [
        'title' => 'desc',
        'created_at',
        'counter',
    ];

    protected $guarded = [];

    public function filters(): array
    {
        return [
            'frontend' => collect([
                new PublishedFilter(),
            ]),
            'backend' => [
                new CounterFilter(),
            ],
            '__default' => [
                new PostFilter(),

                (new PostFilter())
                    ->setTitle(ucwords(str_replace('_', ' ', 'post_filter_throws')))
                    ->setQueryName('post_filter_throws')
                    ->setValidationMode(ValidationMode::THROW),

                (new PostFilter())
                    ->setTitle(ucwords(str_replace('_', ' ', 'post_filter_multi')))
                    ->setQueryName('post_filter_multi')
                    ->setMode(FilterMode::CONTAINS),
            ],
        ];
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
