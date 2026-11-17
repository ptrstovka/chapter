<?php

use App\Enums\AccessGroupVisibility;
use App\Enums\CourseAccessMode;
use App\Enums\CourseAccessRequestStatus;
use App\Enums\Preference;
use App\Facades\Settings;
use App\Models\AccessGroup;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAccessRequest;
use App\Models\CourseEnrollment;
use App\Models\CourseGroupAccess;
use App\Models\CourseUserAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function makeStudioAccessCourseFor(User $creator, CourseAccessMode $accessMode = CourseAccessMode::Public): Course
{
    return Course::factory()
        ->for($creator->author)
        ->for(Category::factory())
        ->create([
            'access_mode' => $accessMode,
        ]);
}

it('shows course access management props to creators without private admin group grants', function () {
    $creator = User::factory()->author()->create();
    $admin = User::factory()->admin()->create();
    $course = makeStudioAccessCourseFor($creator);

    $directUser = User::factory()->create([
        'email' => 'direct@example.com',
        'name' => 'Direct Learner',
    ]);

    CourseUserAccess::factory()
        ->for($course)
        ->for($directUser)
        ->for($creator, 'grantedBy')
        ->create();

    $publicGroup = AccessGroup::factory()->create([
        'name' => 'Public Cohort',
    ]);
    $publicGroup->users()->attach(User::factory()->count(2)->create());

    $privateGroup = AccessGroup::factory()->private()->create([
        'name' => 'Private Cohort',
    ]);
    $privateGroup->users()->attach(User::factory()->create());

    CourseGroupAccess::factory()
        ->for($course)
        ->for($publicGroup, 'accessGroup')
        ->for($creator, 'grantedBy')
        ->create();

    CourseGroupAccess::factory()
        ->for($course)
        ->for($privateGroup, 'accessGroup')
        ->for($admin, 'grantedBy')
        ->create();

    AccessGroup::factory()->create([
        'name' => 'Available Public',
    ]);

    AccessGroup::factory()->private()->create([
        'name' => 'Available Private',
    ]);

    CourseAccessRequest::factory()->create([
        'course_id' => $course->id,
        'user_id' => User::factory()->create(['email' => 'request@example.com'])->id,
    ]);

    $this->actingAs($creator)
        ->get(route('studio.courses.access', $course->uuid))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Studio/CourseAccess')
            ->where('accessMode', CourseAccessMode::Public->value)
            ->has('accessModeOptions', 3)
            ->has('directGrants', 1)
            ->where('directGrants.0.user.email', 'direct@example.com')
            ->has('groupGrants', 1)
            ->where('groupGrants.0.group.name', 'Public Cohort')
            ->where('groupGrants.0.group.usersCount', 2)
            ->has('availableGroups', 1)
            ->where('availableGroups.0.name', 'Available Public')
            ->has('pendingRequests', 1)
            ->where('pendingRequests.0.user.email', 'request@example.com')
            ->where('userDirectory.searchable', false)
        );
});

it('lets admins manage private group grants on the studio access screen', function () {
    $creator = User::factory()->author()->create();
    $admin = User::factory()->admin()->create();
    $course = makeStudioAccessCourseFor($creator);

    $privateGrantGroup = AccessGroup::factory()->private()->create([
        'name' => 'Private Grant',
    ]);

    CourseGroupAccess::factory()
        ->for($course)
        ->for($privateGrantGroup, 'accessGroup')
        ->for($admin, 'grantedBy')
        ->create();

    AccessGroup::factory()->private()->create([
        'name' => 'Available Private',
    ]);

    $this->actingAs($admin)
        ->get(route('studio.courses.access', $course->uuid))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Studio/CourseAccess')
            ->has('groupGrants', 1)
            ->where('groupGrants.0.group.visibility', AccessGroupVisibility::Private->value)
            ->has('availableGroups', 1)
            ->where('availableGroups.0.visibility', AccessGroupVisibility::Private->value)
        );
});

it('updates a course access mode from studio', function () {
    $creator = User::factory()->author()->create();
    $course = makeStudioAccessCourseFor($creator);

    $this->actingAs($creator)
        ->patch(route('studio.courses.access.update', $course->uuid), [
            'access_mode' => CourseAccessMode::RequestOnly->value,
        ])
        ->assertRedirect();

    expect($course->refresh()->access_mode)->toBe(CourseAccessMode::RequestOnly);
});

it('grants and revokes direct user access without removing enrollment progress', function () {
    $creator = User::factory()->author()->create();
    $course = makeStudioAccessCourseFor($creator, CourseAccessMode::Private);
    $learner = User::factory()->create();

    $this->actingAs($creator)
        ->post(route('studio.courses.access.users.store', $course->uuid), [
            'users' => [$learner->id],
            'email' => $learner->email,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('course_user_access', [
        'course_id' => $course->id,
        'user_id' => $learner->id,
        'granted_by_id' => $creator->id,
    ]);

    $enrollment = CourseEnrollment::create([
        'course_id' => $course->id,
        'user_id' => $learner->id,
        'started_at' => now(),
        'progress' => 45,
    ]);

    $this->actingAs($creator)
        ->delete(route('studio.courses.access.users.destroy', $course->uuid), [
            'users' => [$learner->id],
        ])
        ->assertRedirect();

    $this->assertDatabaseMissing('course_user_access', [
        'course_id' => $course->id,
        'user_id' => $learner->id,
    ]);

    $this->assertDatabaseHas('course_enrollments', [
        'id' => $enrollment->id,
        'progress' => 45,
    ]);
});

it('limits creator bulk direct grants when the studio directory is disabled', function () {
    Settings::set(Preference::EnableStudioUserDirectory, false);

    $creator = User::factory()->author()->create();
    $admin = User::factory()->admin()->create();
    $course = makeStudioAccessCourseFor($creator);
    $learners = User::factory()->count(2)->create();

    $this->actingAs($creator)
        ->post(route('studio.courses.access.users.store', $course->uuid), [
            'users' => $learners->pluck('id')->all(),
        ])
        ->assertSessionHasErrors('users');

    $this->actingAs($admin)
        ->post(route('studio.courses.access.users.store', $course->uuid), [
            'users' => $learners->pluck('id')->all(),
        ])
        ->assertRedirect();

    expect(CourseUserAccess::query()->whereBelongsTo($course)->count())->toBe(2);
});

it('requires the matching exact email when a creator grants access without the directory', function (mixed $email, string $error) {
    Settings::set(Preference::EnableStudioUserDirectory, false);

    $creator = User::factory()->author()->create();
    $course = makeStudioAccessCourseFor($creator);
    $learner = User::factory()->create(['email' => 'learner@example.com']);

    $this->actingAs($creator)
        ->post(route('studio.courses.access.users.store', $course->uuid), [
            'users' => [$learner->id],
            'email' => $email,
        ])
        ->assertSessionHasErrors($error);

    expect($course->userAccessGrants()->exists())->toBeFalse();
})->with([
    'guessed user id without email' => [null, 'email'],
    'email belongs to a different user' => ['someone-else@example.com', 'users.0'],
    'malformed email' => ['learner', 'email'],
    'email array' => [['learner@example.com'], 'email'],
]);

it('matches grant emails without case or surrounding whitespace sensitivity', function () {
    Settings::set(Preference::EnableStudioUserDirectory, false);
    $creator = User::factory()->author()->create();
    $course = makeStudioAccessCourseFor($creator);
    $learner = User::factory()->create(['email' => 'Learner@example.com']);

    $this->actingAs($creator)
        ->post(route('studio.courses.access.users.store', $course->uuid), [
            'users' => [$learner->id],
            'email' => '  LEARNER@example.com  ',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($course->hasAccessGrantFor($learner))->toBeTrue();
});

it('still permits creator bulk grants when directory search is enabled', function () {
    Settings::set(Preference::EnableStudioUserDirectory, true);
    $creator = User::factory()->author()->create();
    $course = makeStudioAccessCourseFor($creator);
    $learners = User::factory()->count(2)->create();

    $this->actingAs($creator)
        ->post(route('studio.courses.access.users.store', $course->uuid), [
            'users' => $learners->modelKeys(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($course->userAccessGrants()->count())->toBe(2);
});

it('limits creator group grants to public groups while admins can grant private groups', function () {
    $creator = User::factory()->author()->create();
    $admin = User::factory()->admin()->create();
    $course = makeStudioAccessCourseFor($creator);
    $privateGroup = AccessGroup::factory()->private()->create();

    $this->actingAs($creator)
        ->post(route('studio.courses.access.groups.store', $course->uuid), [
            'groups' => [$privateGroup->id],
        ])
        ->assertSessionHasErrors('groups.0');

    $this->actingAs($admin)
        ->post(route('studio.courses.access.groups.store', $course->uuid), [
            'groups' => [$privateGroup->id],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('course_group_access', [
        'course_id' => $course->id,
        'access_group_id' => $privateGroup->id,
        'granted_by_id' => $admin->id,
    ]);
});

it('approves and denies pending access requests', function () {
    $creator = User::factory()->author()->create();
    $course = makeStudioAccessCourseFor($creator, CourseAccessMode::RequestOnly);
    $approvedLearner = User::factory()->create();
    $deniedLearner = User::factory()->create();

    $approvedRequest = CourseAccessRequest::factory()->create([
        'course_id' => $course->id,
        'user_id' => $approvedLearner->id,
    ]);

    $deniedRequest = CourseAccessRequest::factory()->create([
        'course_id' => $course->id,
        'user_id' => $deniedLearner->id,
    ]);

    $this->actingAs($creator)
        ->post(route('studio.courses.access.requests.approve', [$course->uuid, $approvedRequest]))
        ->assertRedirect();

    $this->assertDatabaseHas('course_user_access', [
        'course_id' => $course->id,
        'user_id' => $approvedLearner->id,
        'granted_by_id' => $creator->id,
    ]);

    expect($approvedRequest->refresh())
        ->status->toBe(CourseAccessRequestStatus::Approved)
        ->resolved_by_id->toBe($creator->id)
        ->resolved_at->not->toBeNull();

    $this->actingAs($creator)
        ->post(route('studio.courses.access.requests.deny', [$course->uuid, $deniedRequest]))
        ->assertRedirect();

    $this->assertDatabaseMissing('course_user_access', [
        'course_id' => $course->id,
        'user_id' => $deniedLearner->id,
    ]);

    expect($deniedRequest->refresh())
        ->status->toBe(CourseAccessRequestStatus::Denied)
        ->resolved_by_id->toBe($creator->id)
        ->resolved_at->not->toBeNull();
});
