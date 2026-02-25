<?php

namespace Tests\Models\Shape;

use Illuminate\Database\Eloquent\Model;

class ShapePost extends Model
{
    protected $table = 'shape_posts';

    public function tags()
    {
        return $this->belongsToMany(ShapeTag::class, 'shape_post_tag', 'shape_post_id', 'shape_tag_id');
    }
}
