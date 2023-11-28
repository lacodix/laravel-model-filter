<?php

use Tests\Models\Post;

beforeEach(function () {
    $this->post = Post::factory()->create();
});

it('receives sortable fields if no direction is given', function () {
    $ref = new ReflectionClass(Post::class);
    $attr = $ref->getProperty('sortable');
    $attr->setValue($this->post, [
        'title',
        'created_at',
        'counter',
    ]);

    expect($this->post->sortableFields())->toEqual([
        'title' => null,
        'created_at' => null,
        'counter' => null,
    ]);
});

it('receives sortable fields if directions are given', function () {
    $ref = new ReflectionClass(Post::class);
    $attr = $ref->getProperty('sortable');
    $attr->setValue($this->post, [
        'title' => 'asc',
        'created_at' => 'desc',
        'counter' => 'asc',
    ]);

    expect($this->post->sortableFields())->toEqual([
        'title' => 'asc',
        'created_at' => 'desc',
        'counter' => 'asc',
    ]);
});

it('receives sortable fields if only some directions are given', function () {
    $ref = new ReflectionClass(Post::class);
    $attr = $ref->getProperty('sortable');
    $attr->setValue($this->post, [
        'title',
        'created_at' => 'desc',
        'counter',
    ]);

    expect($this->post->sortableFields())->toEqual([
        'title' => null,
        'created_at' => 'desc',
        'counter' => null,
    ]);
});
