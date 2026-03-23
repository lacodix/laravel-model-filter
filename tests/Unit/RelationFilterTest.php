<?php

use Tests\Filters\CommentAuthorNameFilter;
use Tests\Models\Comment;
use Tests\Models\Post;

it('can be filtered by relation', function () {
    $post1 = Post::factory()->create(['title' => 'Post A']);
    $post2 = Post::factory()->create(['title' => 'Post B']);

    Comment::factory()->count(3)->create(['post_id' => $post1->id]);
    Comment::factory()->count(2)->create(['post_id' => $post2->id]);

    $filter = new CommentAuthorNameFilter();
    
    // We expect this to fail currently because RunsOnRelation doesn't exist
    $query = Comment::filter(['comment_author_name_filter' => 'Post A']);
    
    expect($query->count())->toEqual(3);
});
