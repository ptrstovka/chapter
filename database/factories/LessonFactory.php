<?php

namespace Database\Factories;

use App\Enums\TextContentType;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
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
            'position_within_course' => 1,
        ];
    }
}
