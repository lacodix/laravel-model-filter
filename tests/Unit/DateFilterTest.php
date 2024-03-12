<?php

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Models\Post;

beforeEach(function () {
    Post::factory()
        ->count(15)
        ->create([
            'created_at' => $this->faker->dateTimeBetween('-10 week', '-3 week'),
        ]);

    Post::factory()
        ->count(7)
        ->create([
            'created_at' => $this->faker->dateTimeBetween('-5 day', '-1 day'),
        ]);

    Post::factory()
        ->count(4)
        ->create([
            'created_at' => $this->faker->dateTimeBetween('+1 day', '+5 day'),
        ]);

    Post::factory()
        ->count(10)
        ->create([
            'created_at' => $this->faker->dateTimeBetween('+3 week', '+10 week'),
        ]);

    Post::factory()
        ->create([
            'created_at' => Carbon::now()->addWeek(),
        ]);

    Post::factory()
        ->create([
            'created_at' => Carbon::now(),
        ]);

    Post::factory()
        ->create([
            'created_at' => Carbon::now()->subWeek(),
        ]);
});

it('can be filtered by date lower than', function () {
    expect(Post::filter([
        'created_at_lower_filter' => Carbon::now()->subWeeks(2)->format('Y-m-d'),
    ])->count())->toEqual(15);
});

it('can be filtered by date greater than', function () {
    expect(Post::filter([
        'created_at_greater_filter' => Carbon::now()->addWeeks(2)->format('Y-m-d'),
    ])->count())->toEqual(10);
});

it('can be filtered by date between', function () {
    expect(Post::filter([
        'created_at_between' => [
            Carbon::now()->subWeek()->format('Y-m-d'),
            Carbon::now()->addWeek()->format('Y-m-d'),
        ],
    ])->count())->toEqual(14);
});

it('cannot be filtered by date between with wrong order', function () {
    expect(Post::filter([
        'created_at_between' => [
            Carbon::now()->addWeek()->format('Y-m-d'),
            Carbon::now()->subWeek()->format('Y-m-d'),
        ],
    ])->count())->toEqual(0);
});

it('can be filtered by date between exclusive', function () {
    expect(Post::filter([
        'created_at_between_exclusive' => [
            Carbon::now()->subWeek()->format('Y-m-d'),
            Carbon::now()->addWeek()->format('Y-m-d'),
        ],
    ])->count())->toEqual(12);
});

it('can be filtered by date not between', function () {
    expect(Post::filter([
        'created_at_not_between' => [
            Carbon::now()->subWeek()->format('Y-m-d'),
            Carbon::now()->addWeek()->format('Y-m-d'),
        ],
    ])->count())->toEqual(25);
});

it('cannot be filtered by date not between with wrong order', function () {
    expect(Post::filter([
        'created_at_not_between' => [
            Carbon::now()->addWeek()->format('Y-m-d'),
            Carbon::now()->subWeek()->format('Y-m-d'),
        ],
    ])->count())->toEqual(39);
});

it('can be filtered by date not between inclusive', function () {
    expect(Post::filter([
        'created_at_not_between_inclusive' => [
            Carbon::now()->subWeek()->format('Y-m-d'),
            Carbon::now()->addWeek()->format('Y-m-d'),
        ],
    ])->count())->toEqual(27);
});

it('can be filtered by date exact', function () {
    expect(Post::filter([
        'created_at_today' => Carbon::now()->format('Y-m-d'),
    ])->count())->toEqual(1);
});

it('is doesn\'t apply if values are invalid', function () {
    expect(Post::filter([
        'created_at_between' => [
            Carbon::now()->subWeek()->format('Y-m-d'),
            'asdf',
        ],
    ])->count())->toEqual(39);
});

it('is doesn\'t apply if single value is invalid', function () {
    expect(Post::filter([
        'created_at_greater_filter' => 'asdf',
    ])->count())->toEqual(39);
});

it('is invalid with non date formats on multi value', function () {
    Post::filter([
        'created_at_between_throws' => [
            Carbon::now()->subWeek()->format('Y-m-d'),
            'asdf',
        ],
    ]);
})->throws(ValidationException::class);

it('is invalid with non date formats on single value', function () {
    Post::filter([
        'created_at_greater_filter_throws' => 'asdf',
    ]);
})->throws(ValidationException::class);
