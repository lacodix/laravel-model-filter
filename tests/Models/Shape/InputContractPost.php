<?php

declare(strict_types=1);

namespace Tests\Models\Shape;

use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Tests\Models\Tag;

class InputContractPost extends Model
{
    use HasFilters;

    /** @var array<int, mixed> */
    public static array $configuredFilters = [];

    protected $table = 'posts';

    protected $guarded = [];

    public function filters(): array
    {
        return self::$configuredFilters;
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id')
            ->withPivot(['start', 'end']);
    }
}
