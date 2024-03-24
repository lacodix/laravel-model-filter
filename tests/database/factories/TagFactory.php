<?php

namespace Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->words(random_int(1, 3), true),
        ];
    }
}
