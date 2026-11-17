<?php

use App\Enums\CourseAccessMode;
use App\Enums\CourseAccessRequestStatus;
use App\Models\AccessGroup;
use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAccessRequest;
use App\Models\CourseEnrollment;
use App\Models\CourseGroupAccess;
use App\Models\CourseUserAccess;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function makeLearnerAccessCourse(CourseAccessMode $accessMode = CourseAccessMode::Public): Course
{
    return Course::factory()
        ->for(Author::factory())
        ->for(Category::factory())
        ->create([
            'access_mode' => $accessMode,
        ]);
}

function enrollLearnerInCourse(User $user, Course $course): CourseEnrollment
{
    return CourseEnrollment::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'started_at' => now(),
        'progress' => 0,
    ]);
}

it('hides private courses from learners until they have direct access', function () {
    $user = User::factory()->create();
    $course = makeLearnerAccessCourse(CourseAccessMode::Private);

    $this->actingAs($user)
        ->get(route('courses'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 0)
        );

    $this->actingAs($user)
        ->get(route('courses.show', $course->slug))
        ->assertNotFound();

    CourseUserAccess::factory()
        ->for($course)
        ->for($user)
        ->for(User::factory()->admin()->create(), 'grantedBy')
        ->create();

    $this->actingAs($user)
        ->get(route('courses.show', $course->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('access.mode', CourseAccessMode::Private->value)
            ->where('access.canEnroll', true)
        );
});

it('lets learners request access for approval required courses before enrollment', function () {
    $user = User::factory()->create();
    $course = makeLearnerAccessCourse(CourseAccessMode::RequestOnly);

    $this->actingAs($user)
        ->get(route('courses.show', $course->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('access.mode', CourseAccessMode::RequestOnly->value)
            ->where('access.canEnroll', false)
            ->where('access.canRequest', true)
            ->where('access.hasPendingRequest', false)
        );

    $this->actingAs($user)
        ->post(route('courses.enroll', $course->uuid))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('courses.access-requests.store', $course->uuid))
        ->assertRedirect();

    expect(CourseAccessRequest::query()
        ->whereBelongsTo($course)
        ->whereBelongsTo($user)
        ->where('status', CourseAccessRequestStatus::Pending->value)
        ->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('courses.access-requests.store', $course->uuid))
        ->assertRedirect();

    expect(CourseAccessRequest::query()
        ->whereBelongsTo($course)
        ->whereBelongsTo($user)
        ->where('status', CourseAccessRequestStatus::Pending->value)
        ->count())->toBe(1);
});

it('allows enrollment through dynamic group access and blocks study after membership is removed', function () {
    $user = User::factory()->create();
    $course = makeLearnerAccessCourse(CourseAccessMode::Private);
    $accessGroup = AccessGroup::factory()->create();

    $accessGroup->users()->attach($user);

    CourseGroupAccess::factory()
        ->for($course)
        ->for($accessGroup, 'accessGroup')
        ->for(User::factory()->admin()->create(), 'grantedBy')
        ->create();

    $this->actingAs($user)
        ->post(route('courses.enroll', $course->uuid))
        ->assertRedirect(route('courses.begin', $course->slug));

    $accessGroup->users()->detach($user);

    $this->actingAs($user)
        ->get(route('courses.show', $course->slug))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('courses.begin', $course->slug))
        ->assertForbidden();
});

it('shows favorited approval required courses before access is granted', function (bool $hasPendingRequest) {
    $user = User::factory()->create();
    $course = makeLearnerAccessCourse(CourseAccessMode::RequestOnly);

    $this->actingAs($user)
        ->post(route('courses.favorite', $course->slug))
        ->assertRedirect();

    if ($hasPendingRequest) {
        $this->post(route('courses.access-requests.store', $course->uuid))
            ->assertRedirect();
    }

    $this->get(route('mycourses.favorite'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('favorites.data', 1)
            ->where('favorites.data.0.slug', $course->slug)
            ->where('favorites.data.0.isFavorite', true)
        );

    $this->post(route('courses.enroll', $course->uuid))
        ->assertForbidden();
})->with([
    'before requesting access' => false,
    'while awaiting approval' => true,
]);

it('hides inaccessible favorites without deleting the favorite record', function () {
    $user = User::factory()->create();
    $course = makeLearnerAccessCourse(CourseAccessMode::Private);
    $grant = CourseUserAccess::factory()
        ->for($course)
        ->for($user)
        ->for(User::factory()->admin()->create(), 'grantedBy')
        ->create();

    $user->favoriteCourses()->attach($course);

    $this->actingAs($user)
        ->get(route('mycourses.favorite'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('favorites.data', 1)
        );

    $grant->delete();

    $this->actingAs($user)
        ->get(route('mycourses.favorite'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('favorites.data', 0)
        );

    expect($user->favoriteCourses()->where('course_id', $course->id)->exists())->toBeTrue();
});

it('does not grandfather enrolled learners when a public course becomes private', function () {
    $user = User::factory()->create();
    $course = makeLearnerAccessCourse();

    enrollLearnerInCourse($user, $course);

    $course->update([
        'access_mode' => CourseAccessMode::Private,
    ]);

    $this->actingAs($user)
        ->get(route('courses.begin', $course->slug))
        ->assertForbidden();
});

it('protects lesson and resource access with the current course grant', function () {
    $user = User::factory()->create();
    $course = makeLearnerAccessCourse(CourseAccessMode::Private);
    enrollLearnerInCourse($user, $course);

    $resource = Resource::create([
        'course_id' => $course->id,
        'file_path' => 'missing-file.pdf',
        'client_file_name' => 'missing-file.pdf',
        'size' => 1,
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($user)
        ->get(route('resources.show', [$course->slug, $resource->uuid]))
        ->assertForbidden();
});
