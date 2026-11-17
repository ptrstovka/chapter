<?php

namespace Database\Factories;

use App\Enums\CourseAccessRequestStatus;
use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAccessRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseAccessRequest>
 */
class CourseAccessRequestFactory extends Factory
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
            'status' => CourseAccessRequestStatus::Pending,
            'resolved_by_id' => null,
            'resolved_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => CourseAccessRequestStatus::Pending,
            'resolved_by_id' => null,
            'resolved_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => CourseAccessRequestStatus::Approved,
            'resolved_by_id' => User::factory(),
            'resolved_at' => now(),
        ]);
    }

    public function denied(): static
    {
        return $this->state([
            'status' => CourseAccessRequestStatus::Denied,
            'resolved_by_id' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
