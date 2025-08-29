<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\Models\Post;

beforeEach(function () {
    // Seed posts similar to SearchTest to keep expected counts consistent
    Post::factory()->state(['title' => 'no '.$this->faker->words(4, true).' test'])->create();
    Post::factory()->state(['title' => 'no '.$this->faker->words(2, true).' test '.$this->faker->words(2, true).' no'])->create();
    Post::factory()->state(['title' => 'not to be found','content' => 'also not to be found'])->create();
    Post::factory()->state(['title' => 'test '.$this->faker->words(4, true).' no'])->create();
    Post::factory()->state(['content' => 'START '.$this->faker->words(4, true).' no'])->create();
    Post::factory()->state(['title' => 'test'])->create();
    Post::factory()->state(['content' => 'no '.$this->faker->words(4, true).' test'])->create();
    Post::factory()->state(['content' => 'no '.$this->faker->words(4, true).' THEEND'])->create();
    Post::factory()->state(['content' => 'no '.$this->faker->words(2, true).' test fake foobar '.$this->faker->words(2, true).' no'])->create();
    Post::factory()->state(['content' => 'no '.$this->faker->words(2, true).' TEST fake FOOBAR '.$this->faker->words(2, true).' no'])->create();
    Post::factory()->state(['content' => 'test '.$this->faker->words(4, true).' no'])->create();
    Post::factory()->state(['content' => 'test'])->create();
});

it('searches via query string default parameter', function () {
    // default config names: search, search_for
    $request = Request::create('/posts', 'GET', ['search' => 'test']);
    $this->app->instance('request', $request);

    expect(Post::query()->searchByQueryString()->count())->toEqual(9);
});

it('searches via query string with single field override', function () {
    $request = Request::create('/posts', 'GET', [
        'search' => 'test',
        'search_for' => 'title',
    ]);
    $this->app->instance('request', $request);

    expect(Post::query()->searchByQueryString()->count())->toEqual(4);
});

it('searches via query string with multiple fields array override', function () {
    $request = Request::create('/posts', 'GET', [
        'search' => 'test',
        'search_for' => ['title', 'content'],
    ]);
    $this->app->instance('request', $request);

    expect(Post::query()->searchByQueryString()->count())->toEqual(9);
});

it('searches via query string with mode overrides', function () {
    $request = Request::create('/posts', 'GET', [
        'search' => 'test',
        'search_for' => [
            'title' => 'equal',
            'content' => 'like',
        ],
    ]);
    $this->app->instance('request', $request);

    expect(Post::query()->searchByQueryString()->count())->toEqual(6);
});

it('honors custom query parameter names from config', function () {
    Config::set('model-filter.search_query_value_name', 'q');
    Config::set('model-filter.search_query_fields_name', 'in');

    $request = Request::create('/posts', 'GET', [
        'q' => 'test',
        'in' => 'title',
    ]);
    $this->app->instance('request', $request);

    expect(Post::query()->searchByQueryString()->count())->toEqual(4);
});
