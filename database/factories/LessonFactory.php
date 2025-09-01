<?php

namespace Database\Factories;

use App\Enums\TextContentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence;

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraphs(asText: true),
            'description_type' => TextContentType::Html,
            'position' => 1,
        ];
    }
}
