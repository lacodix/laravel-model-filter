<?php

namespace Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->words(random_int(5, 15), true),
            'type' => $this->faker->boolean ? 'post' : 'page',
            'created_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'published' => $this->faker->boolean,
            'content' => $this->faker->text,
            'counter' => $this->faker->randomNumber(5),
        ];
    }
}
