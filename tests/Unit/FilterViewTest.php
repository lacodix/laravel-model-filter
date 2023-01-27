<?php

use Tests\Models\Post;

beforeEach(function () {
    Post::factory()
        ->state([
            'type' => 'page',
        ])
        ->count(15)
        ->create();

    Post::factory()
        ->state([
            'type' => 'post',
        ])
        ->count(10)
        ->create();
});

it('can be filtered by select', function () {
    $view = $this->blade('
        <x-lacodix-filter::model-filters model="'.Post::class.'" />
    ');

    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_lower_filter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_greater_filter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_between')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_between_exclusive')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_not_between')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_not_between_inclusive')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_today')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_greater_filter_throws')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_between_throws')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'type_filter_throws')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'starts_with')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'ends_with')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'contains')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'equals')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'boolfilter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_lower_filter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_greater_filter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_between')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_between_exclusive')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_not_between')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_not_between_inclusive')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_exact')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_greater_filter_throws')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_between_throws')));
});
