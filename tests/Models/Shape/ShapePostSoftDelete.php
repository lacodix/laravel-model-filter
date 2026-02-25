<?php

namespace Tests\Models\Shape;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShapePostSoftDelete extends Model
{
    use SoftDeletes;

    protected $table = 'shape_posts';

    public function tags()
    {
        return $this->belongsToMany(ShapeTag::class, 'shape_post_tag', 'shape_post_id', 'shape_tag_id');
    }
}
