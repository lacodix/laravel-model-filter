<?php

use Illuminate\Validation\ValidationException;
use Tests\Models\Post;

beforeEach(function () {
    Post::factory()
        ->count(15)
        ->create([
            'counter' => $this->faker->numberBetween(0, 5000)
        ]);

    Post::factory()
        ->count(7)
        ->create([
            'counter' => $this->faker->numberBetween(6000, 9000)
        ]);

    Post::factory()
        ->count(4)
        ->create([
            'counter' => $this->faker->numberBetween(11000, 14000)
        ]);

    Post::factory()
        ->count(10)
        ->create([
            'counter' => $this->faker->numberBetween(15000, 20000)
        ]);

    Post::factory()
        ->create([
            'counter' => 5500,
        ]);

    Post::factory()
        ->create([
            'counter' => 10000,
        ]);

    Post::factory()
        ->create([
            'counter' => 14500,
        ]);
});

it('can be filtered by number lower than', function () {
    expect(Post::filter([
        'counter_lower_filter' => 5000,
    ])->count())->toEqual(15);
});

it('can be filtered by number greater than', function () {
    expect(Post::filter([
        'counter_greater_filter' => 15000,
    ])->count())->toEqual(10);
});

it('can be filtered by number between', function () {
    expect(Post::filter([
        'counter_between' => [
            5500,
            14500,
        ],
    ])->count())->toEqual(14);
});

it('cannot be filtered by number between with wrong order', function () {
    expect(Post::filter([
        'counter_between' => [
            14500,
            5500,
        ],
    ])->count())->toEqual(0);
});

it('can be filtered by number between exclusive', function () {
    expect(Post::filter([
        'counter_between_exclusive' => [
            5500,
            14500,
        ],
    ])->count())->toEqual(12);
});

it('can be filtered by number not between', function () {
    expect(Post::filter([
        'counter_not_between' => [
            5500,
            14500,
        ],
    ])->count())->toEqual(25);
});

it('cannot be filtered by number not between with wrong order', function () {
    expect(Post::filter([
        'counter_not_between' => [
            14500,
            5500,
        ],
    ])->count())->toEqual(39); // finds all - <= 14500 or >= 5500
});

it('can be filtered by number not between inclusive', function () {
    expect(Post::filter([
        'counter_not_between_inclusive' => [
            5500,
            14500,
        ],
    ])->count())->toEqual(27);
});

it('can be filtered by number exact', function () {
    expect(Post::filter([
        'counter_exact' => 10000,
    ])->count())->toEqual(1);
});

it('is doesn\'t apply if values are invalid', function () {
    expect(Post::filter([
        'counter_between' => [
            1000,
            'asdf',
        ],
    ])->count())->toEqual(39);
});

it('is doesn\'t apply if single value is invalid', function () {
    expect(Post::filter([
        'counter_greater_filter' => 'asdf',
    ])->count())->toEqual(39);
});

it('is doesn\'t apply if value is out of min/max', function () {
    Post::factory()
        ->count(2)
        ->create([
            'counter' => $this->faker->numberBetween(22000, 30000)
        ]);

    expect(Post::filter([
        'counter_filter' => '21000',
    ])->count())->toEqual(41);
});

it('is invalid with non numeric formats on multi value', function () {
    Post::filter([
        'counter_between_throws' => [
            1000,
            'asdf',
        ],
    ]);
})->throws(ValidationException::class);

it('is invalid with non numeric formats on single value', function () {
    Post::filter([
        'counter_greater_filter_throws' => 'asdf',
    ]);
})->throws(ValidationException::class);
