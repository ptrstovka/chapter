<?php

use App\Enums\CourseAccessRequestStatus;
use App\Models\AccessGroup;
use App\Models\Chapter;
use App\Models\CourseAccessRequest;
use App\Models\CourseGroupAccess;
use App\Models\CourseUserAccess;
use App\Models\Invitation;
use App\Models\Lesson;
use App\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('deletes a learner account together with its access grants requests and group memberships', function () {
    $learner = User::factory()->create();
    $grant = CourseUserAccess::factory()->for($learner)->create();
    $request = CourseAccessRequest::factory()
        ->for($learner)
        ->for($grant->course)
        ->create();
    $group = AccessGroup::factory()->create();
    $group->users()->attach($learner);

    $this->actingAs($learner)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertModelMissing($learner);
    $this->assertModelMissing($grant);
    $this->assertModelMissing($request);
    $this->assertModelExists($group);
    expect($group->users()->exists())->toBeFalse();
});

it('deletes an active learner account while preserving consumed invitations and temporary upload cleanup', function () {
    Storage::fake('public');

    $learner = User::factory()->create();
    $grant = CourseUserAccess::factory()->for($learner)->create();
    $course = $grant->course;
    $lesson = Lesson::factory()
        ->for($course)
        ->for(Chapter::factory()->for($course))
        ->create();
    $enrollment = $learner->enrolledCourses()->create([
        'course_id' => $course->id,
        'started_at' => now(),
        'progress' => 0,
    ]);
    $completion = $lesson->markCompletedFor($learner);
    $learner->favoriteCourses()->attach($course);

    $invitation = Invitation::create([
        'code' => fake()->uuid(),
        'used_by_id' => $learner->id,
        'used_at' => now(),
    ]);
    Storage::disk('public')->put('tmp/learner-file.txt', 'Temporary content');
    $upload = TemporaryUpload::create([
        'user_id' => $learner->id,
        'disk' => 'public',
        'path' => 'tmp/learner-file.txt',
        'scope' => 'CourseResource',
        'mime_type' => 'text/plain',
        'client_file_name' => 'learner-file.txt',
        'size' => 17,
        'created_at' => now()->subHours(25),
    ]);

    $this->actingAs($learner)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertModelMissing($learner);
    $this->assertModelMissing($grant);
    $this->assertModelMissing($enrollment);
    $this->assertModelMissing($completion);
    $this->assertModelExists($course);
    $this->assertModelExists($lesson);
    $this->assertModelExists($invitation);
    $this->assertModelExists($upload);

    expect($course->favoritedBy()->exists())->toBeFalse()
        ->and($invitation->refresh()->used_by_id)->toBeNull()
        ->and($invitation->used_at)->not->toBeNull()
        ->and($invitation->isValid())->toBeFalse()
        ->and($upload->refresh()->user_id)->toBeNull()
        ->and($upload->prunable()->whereKey($upload->id)->exists())->toBeTrue();

    Storage::disk('public')->assertExists($upload->path);
    $upload->prune();
    $this->assertModelMissing($upload);
    Storage::disk('public')->assertMissing($upload->path);
});

it('preserves grants and resolved request history when the granting administrator deletes their account', function () {
    $admin = User::factory()->admin()->create();
    $grant = CourseUserAccess::factory()->for($admin, 'grantedBy')->create();
    $groupGrant = CourseGroupAccess::factory()
        ->for($grant->course)
        ->for($admin, 'grantedBy')
        ->create();
    $groupGrant->accessGroup->users()->attach($grant->user);

    $approvedRequest = CourseAccessRequest::factory()
        ->approved()
        ->for($grant->course)
        ->for($grant->user)
        ->create(['resolved_by_id' => $admin->id]);
    $deniedRequest = CourseAccessRequest::factory()
        ->denied()
        ->for($grant->course)
        ->create(['resolved_by_id' => $admin->id]);

    $this->actingAs($admin)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertModelMissing($admin);
    $this->assertModelExists($grant);
    $this->assertModelExists($groupGrant);

    expect($grant->refresh()->granted_by_id)->toBeNull()
        ->and($grant->grantedBy)->toBeNull()
        ->and($groupGrant->refresh()->granted_by_id)->toBeNull()
        ->and($groupGrant->grantedBy)->toBeNull()
        ->and($approvedRequest->refresh()->resolved_by_id)->toBeNull()
        ->and($approvedRequest->status)->toBe(CourseAccessRequestStatus::Approved)
        ->and($approvedRequest->resolved_at)->not->toBeNull()
        ->and($deniedRequest->refresh()->resolved_by_id)->toBeNull()
        ->and($deniedRequest->status)->toBe(CourseAccessRequestStatus::Denied)
        ->and($deniedRequest->resolved_at)->not->toBeNull()
        ->and($grant->course->hasAccessGrantFor($grant->user))->toBeTrue();

    $this->withoutVite()->actingAs(User::factory()->admin()->create())
        ->get(route('studio.courses.access', $grant->course->uuid))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('directGrants.0.grantedBy', null)
            ->where('groupGrants.0.grantedBy', null)
        );
});

it('preserves existing access records when upgrading the user deletion constraints', function () {
    $admin = User::factory()->admin()->create();
    $migration = require database_path('migrations/2026_11_17_074901_update_course_access_user_deletion_constraints.php');
    $migration->down();

    $grant = CourseUserAccess::factory()->for($admin, 'grantedBy')->create();
    $groupGrant = CourseGroupAccess::factory()
        ->for($grant->course)
        ->for($admin, 'grantedBy')
        ->create();
    $request = CourseAccessRequest::factory()
        ->approved()
        ->for($grant->course)
        ->for($grant->user)
        ->create(['resolved_by_id' => $admin->id]);

    $migration->up();
    $admin->delete();

    $this->assertModelMissing($admin);
    $this->assertModelExists($grant);
    $this->assertModelExists($groupGrant);
    $this->assertModelExists($request);

    expect($grant->refresh()->granted_by_id)->toBeNull()
        ->and($groupGrant->refresh()->granted_by_id)->toBeNull()
        ->and($request->refresh()->resolved_by_id)->toBeNull()
        ->and($request->status)->toBe(CourseAccessRequestStatus::Approved);
});
