<?php

declare(strict_types=1);

namespace Tests\Models\Shape;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class InputContractSoftDeletePost extends Model
{
    use HasFilters;
    use SoftDeletes;

    /** @var array<int, mixed> */
    public static array $configuredFilters = [];

    public $timestamps = false;

    protected $table = 'shape_posts';

    protected $guarded = [];

    public function filters(): array
    {
        return self::$configuredFilters;
    }
}
