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

    // Post without any tags
    $this->post18 = Post::factory()->create();
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

it('can see posts that never had any tag', function () {
    expect(Post::filter(['tag_timeframe_filter' => ['mode' => 'never', 'values' => []]])->count())->toEqual(1)
        ->and(Post::filter(['tag_timeframe_filter' => ['mode' => 'never', 'values' => []]])->first()->id)->toEqual($this->post18->id);
});

it('can see posts that never had tag1', function () {
    $result = Post::filter(['tag_timeframe_filter' => ['mode' => 'never', 'values' => $this->tag1->id]])->get();

    expect($result->count())->toEqual(2)
        ->and($result->pluck('id')->sort()->values()->all())->toEqual(
            collect([$this->post17->id, $this->post18->id])->sort()->values()->all()
        );
});

it('can see posts where tag1 is not current', function () {
    // Posts where no currently valid tag1 entry exists
    // Current (start <= now or null, AND end >= now or null): post1,2,2a,3,4a,4,9b,10,11,12,14 = 11
    // Not current for tag1: 24-11=13, plus post17+post18 = 15
    expect(Post::filter(['tag_timeframe_filter' => ['mode' => 'not_current', 'values' => $this->tag1->id]])->count())->toEqual(15);
});

it('can see posts where no tag is currently valid', function () {
    // Posts where no tag attachment at all is currently valid
    // post17 has fakeTag with null/null -> currently valid, so excluded
    // 12 posts have at least one current tag, 26-12=14 don't
    expect(Post::filter(['tag_timeframe_filter' => ['mode' => 'not_current', 'values' => []]])->count())->toEqual(14);
});
