<?php

use function Spatie\PestPluginTestTime\testTime;
use Tests\Models\Post;
use Tests\Models\Tag;

beforeEach(function () {
    testTime()->freeze('2022-07-10 12:34:56');

    $this->tag1 = Tag::factory(['title' => 'tag1'])->create();
    $this->fakeTag = Tag::factory(['title' => 'faketag'])->create();

    // Posts mit verschiedenen Startpunkten
    $this->post1 = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => null,
    ])->create();

    $this->post2 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2021, 07, 10),
        'end' => null,
    ])->create();

    $this->post2a = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2021, 12, 10),
        'end' => null,
    ])->create();

    $this->post3 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2022, 04, 10),
        'end' => null,
    ])->create();

    $this->post4a = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2022, 07, 5),
        'end' => null,
    ])->create();

    $this->post4 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2022, 07, 10),
        'end' => null,
    ])->create();

    $this->post4b = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2022, 07, 15),
        'end' => null,
    ])->create();

    $this->post5 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2022, 11, 10),
        'end' => null,
    ])->create();

    $this->post6 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2023, 07, 10),
        'end' => null,
    ])->create();

    // Posts mit verschiedenen Endpunkten
    $this->post7 = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => \Carbon\Carbon::create(2021, 07, 10),
    ])->create();

    $this->post8a = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => \Carbon\Carbon::create(2022, 04, 5),
    ])->create();

    $this->post8 = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => \Carbon\Carbon::create(2022, 04, 10),
    ])->create();

    $this->post9a = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => \Carbon\Carbon::create(2022, 07, 5),
    ])->create();

    $this->post9 = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => \Carbon\Carbon::create(2022, 07, 10),
    ])->create();

    $this->post9b = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => \Carbon\Carbon::create(2022, 07, 15),
    ])->create();

    $this->post10 = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => \Carbon\Carbon::create(2022, 11, 10),
    ])->create();

    $this->post11 = Post::factory()->hasAttached($this->tag1, [
        'start' => null,
        'end' => \Carbon\Carbon::create(2023, 07, 10),
    ])->create();

    // Posts mit beiden Werten
    $this->post12 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2021, 07, 10),
        'end' => \Carbon\Carbon::create(2023, 07, 10),
    ])->create();

    $this->post13a = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2021, 07, 10),
        'end' => \Carbon\Carbon::create(2022, 07, 5),
    ])->create();

    $this->post13 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2021, 07, 10),
        'end' => \Carbon\Carbon::create(2022, 07, 10),
    ])->create();

    $this->post14 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2022, 07, 10),
        'end' => \Carbon\Carbon::create(2024, 07, 10),
    ])->create();

    $this->post14b = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2022, 07, 15),
        'end' => \Carbon\Carbon::create(2024, 07, 10),
    ])->create();

    $this->post15 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2021, 07, 10),
        'end' => \Carbon\Carbon::create(2022, 04, 10),
    ])->create();

    $this->post16 = Post::factory()->hasAttached($this->tag1, [
        'start' => \Carbon\Carbon::create(2022, 11, 10),
        'end' => \Carbon\Carbon::create(2023, 04, 10),
    ])->create();

    // Fake Post
    $this->post17 = Post::factory()->hasAttached($this->fakeTag)->create();
});

it('can see all posts with tag1 when selected ever', function () {
    expect(Post::filter(['tag_timeframe_filter' => ['values' => $this->tag1->id]])->count())->toEqual(24);
});

it('can see all current posts with tag1 when selected current', function () {
    expect(Post::filter(['tag_timeframe_filter' => ['mode' => 'current', 'values' => $this->tag1->id]])->count())->toEqual(17);
});

it('can see all current day posts with tag1 when selected current', function () {
    expect(Post::filter(['tag_timeframe_filter_day' => ['mode' => 'current', 'values' => $this->tag1->id]])->count())->toEqual(13);
});

it('can see all current year posts with tag1 when selected current', function () {
    expect(Post::filter(['tag_timeframe_filter_year' => ['mode' => 'current', 'values' => $this->tag1->id]])->count())->toEqual(22);
});

it('can see all posts with tag1 and timeframe set', function () {
    expect(Post::filter(['tag_timeframe_filter' => [
        'mode' => 'timeframe',
        'from' => '2021-08',
        'to' => '2022-04',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(15);
});

it('can see all posts with tag1 and day timeframe set', function () {
    expect(Post::filter(['tag_timeframe_filter_day' => [
        'mode' => 'timeframe',
        'from' => '2021-08-01',
        'to' => '2022-04-05',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(14);
});

it('can see all posts with tag1 and year timeframe set', function () {
    expect(Post::filter(['tag_timeframe_filter_year' => [
        'mode' => 'timeframe',
        'from' => '2021',
        'to' => '2022',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(23);
});

it('can see all posts with tag1 and starts in timeframe', function () {
    expect(Post::filter(['tag_timeframe_filter' => [
        'mode' => 'start_in_timeframe',
        'from' => '2021-08',
        'to' => '2022-04',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(2);
});

it('can see all posts with tag1 and starts in day timeframe', function () {
    expect(Post::filter(['tag_timeframe_filter_day' => [
        'mode' => 'start_in_timeframe',
        'from' => '2021-08-01',
        'to' => '2022-04-05',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(1);
});

it('can see all posts with tag1 and starts in year timeframe', function () {
    expect(Post::filter(['tag_timeframe_filter_year' => [
        'mode' => 'start_in_timeframe',
        'from' => '2021',
        'to' => '2022',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(14);
});


it('can see all posts with tag1 and ends in timeframe', function () {
    expect(Post::filter(['tag_timeframe_filter' => [
        'mode' => 'end_in_timeframe',
        'from' => '2021-08',
        'to' => '2022-04',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(3);
});

it('can see all posts with tag1 and ends in day timeframe', function () {
    expect(Post::filter(['tag_timeframe_filter_day' => [
        'mode' => 'end_in_timeframe',
        'from' => '2021-08-01',
        'to' => '2022-04-06',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(1);
});

it('can see all posts with tag1 and ends in year timeframe', function () {
    expect(Post::filter(['tag_timeframe_filter_year' => [
        'mode' => 'end_in_timeframe',
        'from' => '2021',
        'to' => '2022',
        'values' => $this->tag1->id,
    ]])->count())->toEqual(10);
});
