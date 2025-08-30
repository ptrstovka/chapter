<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Enums\TextContentType;
use App\Models\Author;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence;

        return [
            'slug' => Str::slug($title),
            'status' => CourseStatus::Published,
            'title' => $title,
            'description' => fake()->paragraphs(asText: true),
            'description_type' => TextContentType::Html,
            'duration_seconds' => 0,
        ];
    }

    public function random(): static
    {
        return $this->state(function (array $attrs) {
            $category = Category::query()->inRandomOrder()->first();

            if (! $category) {
                $category = Category::factory()->create();
            }

            $author = Author::query()->inRandomOrder()->first();
            if (! $author) {
                $author = Author::factory()->create();
            }

            return [
                'category_id' => $category->id,
                'author_id' => $author->id,
            ];
        })->afterCreating(function (Course $course) {
            $lessons = 1;

            for ($i = 0; $i < rand(1, 4); $i++) {
                $chapter = Chapter::factory()->for($course)->create([
                    'position' => $i + 1,
                ]);

                for ($j = 0; $j < rand(3, 8); $j++) {
                    Lesson::factory()->for($chapter)->for($course)->create([
                        'position' => $lessons,
                    ]);

                    $lessons++;
                }
            }
        });
    }
}
