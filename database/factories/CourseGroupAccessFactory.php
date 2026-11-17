<?php

namespace Database\Factories;

use App\Models\AccessGroup;
use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroupAccess;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseGroupAccess>
 */
class CourseGroupAccessFactory extends Factory
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
            'access_group_id' => AccessGroup::factory(),
            'granted_by_id' => User::factory(),
        ];
    }
}
