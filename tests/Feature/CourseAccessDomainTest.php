<?php

use App\Enums\CourseAccessMode;
use App\Enums\CourseAccessRequestStatus;
use App\Models\AccessGroup;
use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAccessRequest;
use App\Models\CourseGroupAccess;
use App\Models\CourseUserAccess;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function createCourseForAccess(): Course
{
    return Course::factory()
        ->for(Author::factory())
        ->for(Category::factory())
        ->create();
}

it('defaults courses to public access and casts the access mode enum', function () {
    $course = createCourseForAccess();

    expect($course->access_mode)->toBe(CourseAccessMode::Public)
        ->and($course->getRawOriginal('access_mode'))->toBe(CourseAccessMode::Public->value);
});

it('records direct course user access grants with the granting user', function () {
    $course = createCourseForAccess();
    $user = User::factory()->create();
    $grantedBy = User::factory()->admin()->create();

    $grant = CourseUserAccess::factory()
        ->for($course)
        ->for($user)
        ->for($grantedBy, 'grantedBy')
        ->create();

    expect($grant->course->is($course))->toBeTrue()
        ->and($grant->user->is($user))->toBeTrue()
        ->and($grant->grantedBy->is($grantedBy))->toBeTrue()
        ->and($course->userAccessGrants()->first()->is($grant))->toBeTrue();
});

it('keeps direct user grants unique per course and user', function () {
    $course = createCourseForAccess();
    $user = User::factory()->create();
    $grantedBy = User::factory()->admin()->create();

    CourseUserAccess::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
        'granted_by_id' => $grantedBy->id,
    ]);

    expect(fn () => CourseUserAccess::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
        'granted_by_id' => $grantedBy->id,
    ]))->toThrow(QueryException::class);
});

it('records course group access grants with the granting user', function () {
    $course = createCourseForAccess();
    $accessGroup = AccessGroup::factory()->create();
    $grantedBy = User::factory()->admin()->create();

    $grant = CourseGroupAccess::factory()
        ->for($course)
        ->for($accessGroup, 'accessGroup')
        ->for($grantedBy, 'grantedBy')
        ->create();

    expect($grant->course->is($course))->toBeTrue()
        ->and($grant->accessGroup->is($accessGroup))->toBeTrue()
        ->and($grant->grantedBy->is($grantedBy))->toBeTrue()
        ->and($course->groupAccessGrants()->first()->is($grant))->toBeTrue();
});

it('stores course access requests as workflow history with resolver metadata', function () {
    $course = createCourseForAccess();
    $user = User::factory()->create();

    $pendingRequest = CourseAccessRequest::factory()->create([
        'course_id' => $course->id,
        'user_id' => $user->id,
    ]);

    $approvedRequest = CourseAccessRequest::factory()
        ->approved()
        ->create([
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);

    expect($pendingRequest->status)->toBe(CourseAccessRequestStatus::Pending)
        ->and($pendingRequest->resolvedBy)->toBeNull()
        ->and($approvedRequest->status)->toBe(CourseAccessRequestStatus::Approved)
        ->and($approvedRequest->resolvedBy)->toBeInstanceOf(User::class)
        ->and($approvedRequest->resolved_at)->not->toBeNull()
        ->and($course->accessRequests()->count())->toBe(2);
});
