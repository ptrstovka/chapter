<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chapter>
 */
class ChapterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence,
            'position' => 1,
        ];
    }
}
