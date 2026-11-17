<?php

use App\Models\Author;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('continues a course according to only the current learners completions', function (array $completedByLearner, array $completedByOtherLearner, int $expectedLessonIndex) {
    $learner = User::factory()->create();
    $otherLearner = User::factory()->create();
    $course = Course::factory()
        ->for(Author::factory())
        ->for(Category::factory())
        ->create();
    $chapter = Chapter::factory()->for($course)->create();
    $lessons = Lesson::factory()
        ->for($course)
        ->for($chapter)
        ->count(3)
        ->sequence(fn (Sequence $sequence) => [
            'position' => $sequence->index + 1,
            'position_within_course' => $sequence->index + 1,
        ])
        ->create();

    foreach ([$learner, $otherLearner] as $user) {
        $user->enrolledCourses()->create([
            'course_id' => $course->id,
            'started_at' => now(),
            'progress' => 0,
        ]);
    }

    foreach ($completedByLearner as $lessonIndex) {
        $lessons[$lessonIndex]->markCompletedFor($learner);
    }

    foreach ($completedByOtherLearner as $lessonIndex) {
        $lessons[$lessonIndex]->markCompletedFor($otherLearner);
    }

    $this->actingAs($learner)
        ->get(route('courses.begin', $course->slug))
        ->assertRedirect(route('lessons.show', [$course->slug, $lessons[$expectedLessonIndex]->slug_id]));
})->with([
    'starts at the first lesson despite another learners progress' => [[], [0], 0],
    'resumes at the learners next unfinished lesson' => [[0], [1], 1],
    'returns to the first lesson after completing the course' => [[0, 1, 2], [], 0],
]);
