<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseUserAccess;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseUserAccess>
 */
class CourseUserAccessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory()
                ->for(Author::factory())
                ->for(Category::factory()),
            'user_id' => User::factory(),
            'granted_by_id' => User::factory(),
        ];
    }
}
